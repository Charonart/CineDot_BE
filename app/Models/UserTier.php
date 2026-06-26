<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTier extends Model
{
    protected $table = 'user_tiers';

    protected $fillable = [
        'user_id',
        'tier',
        'discount_percent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
