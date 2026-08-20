<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RbacRedisAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser;
    private Role $adminRole;
    private Role $staffRole;
    private Permission $refundPerm;
    private Permission $deleteMoviePerm;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();

        $this->adminRole = Role::create(['name' => 'super_admin', 'description' => 'Super Admin']);
        $this->staffRole = Role::create(['name' => 'cskh_staff', 'description' => 'CSKH Staff']);

        $this->refundPerm = Permission::create(['name' => 'refund:ticket', 'description' => 'Refund ticket']);
        $this->deleteMoviePerm = Permission::create(['name' => 'delete:movie', 'description' => 'Delete movie']);

        $this->staffRole->permissions()->attach($this->refundPerm->permission_id);

        $this->adminUser = User::create([
            'username' => 'admin_test',
            'email' => 'admin@cinedot.vn',
            'password' => bcrypt('password123'),
            'fullname' => 'Super Admin Test',
            'is_active' => true,
        ]);
        \App\Models\UserRole::create([
            'user_id' => $this->adminUser->user_id,
            'role_id' => $this->adminRole->role_id,
            'scope_type' => 'system',
        ]);

        $this->staffUser = User::create([
            'username' => 'staff_test',
            'email' => 'staff@cinedot.vn',
            'password' => bcrypt('password123'),
            'fullname' => 'Staff Test',
            'is_active' => true,
        ]);
        \App\Models\UserRole::create([
            'user_id' => $this->staffUser->user_id,
            'role_id' => $this->staffRole->role_id,
            'scope_type' => 'system',
        ]);
    }

    public function test_login_caches_permissions_in_redis_and_returns_permissions_array()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'staff@cinedot.vn',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.permissions.0', 'refund:ticket');

        $redisKey = "user:{$this->staffUser->user_id}:permissions";
        $this->assertTrue((bool) Redis::exists($redisKey));

        $cachedPerms = json_decode(Redis::get($redisKey), true);
        $this->assertContains('refund:ticket', $cachedPerms);
    }

    public function test_permission_middleware_allows_authorized_permission_and_wildcard()
    {
        $service = app(PermissionService::class);
        $service->getUserPermissions($this->staffUser);

        // Staff has refund:ticket -> pass
        $mw = new \App\Http\Middleware\PermissionMiddleware($service);
        $request = \Illuminate\Http\Request::create('/api/v1/test', 'POST');
        $request->setUserResolver(fn() => $this->staffUser);

        $response = $mw->handle($request, fn() => response()->json(['success' => true]), 'refund:ticket');
        $this->assertEquals(200, $response->getStatusCode());

        // Admin has wildcard * -> pass
        $requestAdmin = \Illuminate\Http\Request::create('/api/v1/test', 'POST');
        $requestAdmin->setUserResolver(fn() => $this->adminUser);

        $responseAdmin = $mw->handle($requestAdmin, fn() => response()->json(['success' => true]), 'delete:movie');
        $this->assertEquals(200, $responseAdmin->getStatusCode());
    }

    public function test_permission_middleware_blocks_unauthorized_user_with_403()
    {
        $service = app(PermissionService::class);
        $service->getUserPermissions($this->staffUser);

        // Staff does NOT have delete:movie -> 403 Forbidden
        $mw = new \App\Http\Middleware\PermissionMiddleware($service);
        $request = \Illuminate\Http\Request::create('/api/v1/test', 'DELETE');
        $request->setUserResolver(fn() => $this->staffUser);

        $response = $mw->handle($request, fn() => response()->json(['success' => true]), 'delete:movie');
        $this->assertEquals(403, $response->getStatusCode());
        
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('Bạn không có quyền thực hiện.', $json['message']);
    }

    public function test_logout_clears_redis_permission_cache()
    {
        $redisKey = "user:{$this->staffUser->user_id}:permissions";
        Redis::setex($redisKey, 3600, json_encode(['refund:ticket']));

        $response = $this->actingAs($this->staffUser, 'sanctum')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertFalse((bool) Redis::exists($redisKey));
    }
}
