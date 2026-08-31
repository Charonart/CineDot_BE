<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

trait HasContextRoles
{
    /**
     * Get all roles associated with the user for a specific context.
     */
    public function getContextRoles()
    {
        if (isset($this->cachedContextRoles)) {
            return $this->cachedContextRoles;
        }

        $this->cachedContextRoles = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.role_id')
            ->where('user_roles.user_id', $this->user_id)
            ->select('user_roles.*', 'roles.name as role_name')
            ->get();

        return $this->cachedContextRoles;
    }

    /**
     * Check if user has a specific permission in a given context
     * 
     * @param string $permissionName
     * @param mixed $context (null, string 'system', or Model instance like Cinema/Province)
     * @return bool
     */
    public function hasPermissionTo(string $permissionName, $context = null): bool
    {
        $userRoles = $this->getContextRoles();
        
        if ($userRoles->isEmpty()) {
            $customerRole = Role::where('name', 'customer')->first();
            if ($customerRole) {
                return $this->roleHasPermission($customerRole->role_id, $permissionName);
            }
            return false;
        }

        // Determine scope to check
        $scopeTypeToCheck = 'system';
        $scopeIdToCheck = null;

        if (is_object($context)) {
            $classBaseName = class_basename($context);
            if ($classBaseName === 'Cinema') {
                $scopeTypeToCheck = 'cinema';
                $scopeIdToCheck = $context->cinema_id ?? $context->id;
            } elseif ($classBaseName === 'Province' || $classBaseName === 'Region') {
                $scopeTypeToCheck = 'region';
                $scopeIdToCheck = $context->province_id ?? $context->id;
            }
        } elseif (is_string($context)) {
            $scopeTypeToCheck = $context;
        }

        // 1. Check System Level Roles (Super Admin overrides lower levels)
        $systemRoles = $userRoles->where('scope_type', 'system');
        foreach ($systemRoles as $ur) {
            if ($this->roleHasPermission($ur->role_id, $permissionName)) {
                return true;
            }
        }

        // 2. Check Region Level Roles
        if ($scopeTypeToCheck === 'cinema' && is_object($context) && isset($context->province_id)) {
            $regionRoles = $userRoles->where('scope_type', 'region')->where('scope_id', $context->province_id);
            foreach ($regionRoles as $ur) {
                if ($this->roleHasPermission($ur->role_id, $permissionName)) {
                    return true;
                }
            }
        } elseif ($scopeTypeToCheck === 'region') {
            $regionRoles = $userRoles->where('scope_type', 'region')->where('scope_id', $scopeIdToCheck);
            foreach ($regionRoles as $ur) {
                if ($this->roleHasPermission($ur->role_id, $permissionName)) {
                    return true;
                }
            }
        }

        // 3. Check Cinema Level Roles
        if ($scopeTypeToCheck === 'cinema') {
            $cinemaRoles = $userRoles->where('scope_type', 'cinema')->where('scope_id', $scopeIdToCheck);
            foreach ($cinemaRoles as $ur) {
                if ($this->roleHasPermission($ur->role_id, $permissionName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper check if a Role ID has a specific permission
     */
    protected function roleHasPermission(int $roleId, string $permissionName): bool
    {
        static $rolePermCache = [];

        if (!isset($rolePermCache[$roleId])) {
            $role = Role::find($roleId);
            if (!$role) {
                $rolePermCache[$roleId] = [];
            } elseif (in_array(strtolower($role->name), ['super_admin', 'super admin', 'admin'])) {
                $rolePermCache[$roleId] = ['*'];
            } else {
                $rolePermCache[$roleId] = DB::table('role_permissions')
                    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.permission_id')
                    ->where('role_permissions.role_id', $roleId)
                    ->pluck('permissions.name')
                    ->toArray();
            }
        }

        $perms = $rolePermCache[$roleId];
        return in_array('*', $perms, true) || in_array($permissionName, $perms, true);
    }

    /**
     * Get allowed scope IDs (e.g. Cinema IDs) for a user to view/manage data
     * 
     * @param string $permissionName
     * @param string $targetScopeType (e.g. 'cinema')
     * @return array (Array of IDs, or ['*'] for all)
     */
    public function getAuthorizedScopeIds(string $permissionName, string $targetScopeType = 'cinema'): array
    {
        $userRoles = $this->getContextRoles();
        $allowedIds = [];
        $hasSystemAccess = false;

        foreach ($userRoles as $ur) {
            if ($this->roleHasPermission($ur->role_id, $permissionName)) {
                if ($ur->scope_type === 'system') {
                    $hasSystemAccess = true;
                    break;
                }
                
                if ($targetScopeType === 'cinema') {
                    if ($ur->scope_type === 'cinema' && $ur->scope_id) {
                        $allowedIds[] = (int) $ur->scope_id;
                    } elseif ($ur->scope_type === 'region' && $ur->scope_id) {
                        $cinemaIds = DB::table('cinemas')->where('province_id', $ur->scope_id)->pluck('cinema_id')->toArray();
                        $allowedIds = array_merge($allowedIds, $cinemaIds);
                    }
                }
            }
        }

        if ($hasSystemAccess) {
            return ['*'];
        }

        // Fallback for primary role
        if ($this->role_id && $this->roleHasPermission($this->role_id, $permissionName)) {
            $roleName = strtolower($this->role?->name ?? '');
            if (in_array($roleName, ['admin', 'super_admin', 'super admin'])) {
                return ['*'];
            }
        }

        return array_values(array_unique($allowedIds));
    }

    /**
     * Assign a context-aware role and clear permissions cache.
     */
    public function assignContextRole(int $roleId, string $scopeType = 'system', ?int $scopeId = null): UserRole
    {
        $userRole = UserRole::updateOrCreate(
            [
                'user_id'    => $this->user_id,
                'role_id'    => $roleId,
                'scope_type' => $scopeType,
                'scope_id'   => $scopeId,
            ]
        );

        app(\App\Services\PermissionService::class)->clearPermissionsCache($this->user_id);

        return $userRole;
    }

    /**
     * Revoke a context-aware role and clear permissions cache.
     */
    public function revokeContextRole(int $roleId, string $scopeType = 'system', ?int $scopeId = null): bool
    {
        $deleted = UserRole::where('user_id', $this->user_id)
            ->where('role_id', $roleId)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->delete();

        app(\App\Services\PermissionService::class)->clearPermissionsCache($this->user_id);

        return (bool) $deleted;
    }
}
