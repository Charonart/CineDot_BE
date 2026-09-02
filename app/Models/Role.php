<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class Role extends Model
{
    use FilterableAndSortable;

    protected $primaryKey = 'role_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        )->withPivot('id', 'scope_type', 'scope_id')->withTimestamps();
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'role_id', 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }
}
