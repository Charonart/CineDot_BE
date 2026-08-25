<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) ($user->user_id ?? $user->id) === (int) $id;
});

Broadcast::channel('showtimes.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('admin.dashboard', function ($user) {
    if (!$user) {
        return false;
    }

    $roleName = strtolower($user->role?->name ?? (is_string($user->role) ? $user->role : ''));
    if (in_array($roleName, ['admin', 'super_admin', 'super admin', 'cinema_manager', 'accountant'])) {
        return true;
    }

    if (method_exists($user, 'getAuthorizedScopeIds')) {
        $allowedCinemaIds = $user->getAuthorizedScopeIds('view:report', 'cinema');
        if (!empty($allowedCinemaIds)) {
            return true;
        }
    }

    if (method_exists($user, 'hasPermissionTo')) {
        return $user->hasPermissionTo('view:report')
            || $user->hasPermissionTo('reports.revenue')
            || $user->hasPermissionTo('reports.dashboard.view');
    }

    return false;
});