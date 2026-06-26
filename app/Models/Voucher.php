<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $primaryKey = 'voucher_id';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_order_value',
        'max_discount_value',
        'valid_from',
        'valid_until',
        'usage_limit',
        'is_active',
        'points_cost',
        'voucher_type',
        'combinable_rules',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'points_cost' => 'integer',
        'combinable_rules' => 'array',
    ];
}
