<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTier extends Model
{
    protected $table = 'user_tiers';
    protected $primaryKey = 'user_tier_id';

    protected $fillable = [
        'tier',
        'discount_percent',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'tier_id', 'user_tier_id');
    }
}
