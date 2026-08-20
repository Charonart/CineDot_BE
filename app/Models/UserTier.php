<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class UserTier extends Model
{
    use FilterableAndSortable;

    protected $table = 'user_tiers';
    protected $primaryKey = 'user_tier_id';

    protected $fillable = [
        'tier',
        'min_points',
        'discount_percent',
    ];

    protected $casts = [
        'min_points'       => 'integer',
        'discount_percent' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'tier_id', 'user_tier_id');
    }
}
