<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Traits\FilterableAndSortable;

class Voucher extends Model
{
    use FilterableAndSortable;

    protected $primaryKey = 'voucher_id';

    protected $fillable = [
        'campaign_id',
        'code',
        'title',
        'description',
        'voucher_type',
        'discount_type',
        'discount_value',
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
        'discount_value'     => 'decimal:2',
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

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'voucher_id', 'voucher_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValidNow($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            });
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        $now = Carbon::now();
        $soon = Carbon::now()->addDays($days);
        return $query->where('is_active', true)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [$now, $soon]);
    }
}
