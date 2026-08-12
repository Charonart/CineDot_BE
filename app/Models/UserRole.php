<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $table = 'user_roles';

    protected $fillable = [
        'user_id',
        'role_id',
        'scope_type', // 'system', 'region', 'cinema'
        'scope_id',   // null for system, province_id for region, cinema_id for cinema
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'scope_id', 'cinema_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'scope_id', 'province_id');
    }
}
