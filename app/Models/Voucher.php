<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $primaryKey = 'voucher_id';

    protected $fillable = [
        'campaign_id',
        'code',
        'voucher_type',
        'discount_type',
        'min_order_value',
        'max_discount_value',
        'valid_from',
        'valid_until',
        'system_limit',
        'limit_per_user',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'valid_from'         => 'datetime',
        'valid_until'        => 'datetime',
        'is_active'          => 'boolean',
        'min_order_value'    => 'decimal:2',
        'max_discount_value' => 'decimal:2',
        'system_limit'       => 'integer',
        'limit_per_user'     => 'integer',
        'used_count'         => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    public function userVouchers()
    {
        return $this->hasMany(UserVoucher::class, 'voucher_id', 'voucher_id');
    }

    public function bookingVouchers()
    {
        return $this->hasMany(BookingVoucher::class, 'voucher_id', 'voucher_id');
    }
}
