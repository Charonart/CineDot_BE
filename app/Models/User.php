<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, \App\Traits\HasContextRoles, \App\Traits\FilterableAndSortable;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'email',
        'fullname',
        'avatar',
        'birthday',
        'gender',
        'province_id',
        'role_id',
        'phone',
        'total_points',
        'is_active',
        'email_verified_at',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password'          => 'hashed',
        'birthday'          => 'date',
        'last_login'        => 'datetime',
        'email_verified_at' => 'datetime',
        'total_points'      => 'integer',
        'is_active'         => 'boolean',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('id', 'scope_type', 'scope_id')
            ->withTimestamps();
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'user_id', 'user_id');
    }

    /**
     * Primary role accessor for backwards compatibility
     */
    public function getRoleAttribute()
    {
        $userRoles = $this->relationLoaded('userRoles') ? $this->userRoles : $this->userRoles()->with('role')->get();
        if ($userRoles->isEmpty()) {
            return (object) ['name' => 'customer', 'role_id' => 3];
        }

        $hierarchy = [
            'admin'          => 100,
            'super_admin'    => 100,
            'cinema_manager' => 80,
            'marketing'      => 60,
            'accountant'     => 60,
            'ticket_staff'   => 40,
            'fnb_staff'      => 40,
            'staff'          => 30,
            'customer'       => 10,
        ];

        $sorted = $userRoles->sortByDesc(function ($ur) use ($hierarchy) {
            $roleName = strtolower($ur->role?->name ?? 'customer');
            return $hierarchy[$roleName] ?? 20;
        });

        $topRole = $sorted->first()?->role;
        return $topRole ?: (object) ['name' => 'customer', 'role_id' => 3];
    }

    public function userTier()
    {
        return UserTier::where('min_points', '<=', $this->total_points ?? 0)
            ->orderByDesc('min_points')
            ->first();
    }

    public function nextUserTier()
    {
        return UserTier::where('min_points', '>', $this->total_points ?? 0)
            ->orderBy('min_points', 'asc')
            ->first();
    }


    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }



    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CustomResetPassword($token));
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\CustomVerifyEmail());
    }
}
