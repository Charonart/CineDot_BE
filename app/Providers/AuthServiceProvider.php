<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Tích hợp Context-Aware RBAC vào hệ thống Gate mặc định của Laravel
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability, $args = []) {
            $context = $args[0] ?? null;
            
            if (method_exists($user, 'hasPermissionTo')) {
                // Kiểm tra quyền theo ngữ cảnh (ví dụ: $user->can('edit-movie', $cinema))
                if ($user->hasPermissionTo($ability, $context)) {
                    return true;
                }
            }
            
            // Trả về null để Laravel tiếp tục chạy các Policy khác nếu có
            return null;
        });
    }
}
