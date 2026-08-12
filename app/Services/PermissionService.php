<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Redis;

class PermissionService
{
    /**
     * Fetch user permissions array from Redis cache (or fallback to DB query with 60m TTL).
     * Combines primary role permissions and all context-aware roles.
     */
    public function getUserPermissions(User $user): array
    {
        $redisKey = "user:{$user->user_id}:permissions";
        $cached = Redis::get($redisKey);

        if ($cached) {
            $permissions = json_decode($cached, true);
            if (is_array($permissions)) {
                return $permissions;
            }
        }

        // Cache miss: aggregate permissions from primary role & user_roles
        $permissions = [];

        // 1. Primary role
        $role = $user->role;
        if ($role) {
            if (in_array(strtolower($role->name), ['super_admin', 'super admin', 'admin'])) {
                $permissions[] = '*';
            } else {
                $primaryPerms = $role->permissions()->pluck('name')->toArray();
                $permissions = array_merge($permissions, $primaryPerms);
            }
        }

        // 2. Context-aware roles
        $contextRoles = UserRole::with('role.permissions')->where('user_id', $user->user_id)->get();
        foreach ($contextRoles as $ur) {
            if ($ur->role) {
                if (in_array(strtolower($ur->role->name), ['super_admin', 'super admin', 'admin']) && $ur->scope_type === 'system') {
                    $permissions[] = '*';
                } else {
                    $scopedPerms = $ur->role->permissions->pluck('name')->toArray();
                    $permissions = array_merge($permissions, $scopedPerms);
                }
            }
        }

        if (in_array('*', $permissions)) {
            $permissions = ['*'];
        } else {
            $permissions = array_values(array_unique($permissions));
        }

        $ttl = (int) env('PERMISSION_CACHE_TTL', 3600);
        Redis::setex($redisKey, $ttl, json_encode($permissions));

        return $permissions;
    }

    /**
     * Clear user permissions cache on logout or role update.
     */
    public function clearPermissionsCache(int $userId): void
    {
        $redisKey = "user:{$userId}:permissions";
        Redis::del($redisKey);
    }
}
