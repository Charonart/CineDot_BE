<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;

class PermissionService
{
    /**
     * Fetch user permissions array from Redis cache (or fallback to DB query with 60m TTL).
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

        // Cache miss: load permissions from DB via user role
        $role = $user->role;
        $permissions = [];

        if ($role) {
            if (in_array(strtolower($role->name), ['super_admin', 'super admin', 'admin'])) {
                // Wildcard for Super Admin / Admin
                $permissions = ['*'];
            } else {
                $permissions = $role->permissions()->pluck('name')->toArray();
            }
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
