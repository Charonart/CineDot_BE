<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingVoucher extends Model
{
    protected $table = 'booking_vouchers';

    protected $fillable = [
        'booking_id',
        'voucher_id',
        'discount_amount_applied',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id', 'voucher_id');
    }
}
