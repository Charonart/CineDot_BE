<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'user_id',
        'showtime_id',
        'voucher_id',
        'price_breakdown',
        'final_amount',
        'discount_amount',
        'booking_status',
        'booking_code',
        'notes',
    ];

    protected $casts = [
        'price_breakdown' => 'array',
        'final_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class, 'booking_id', 'booking_id');
    }

    public function bookingCombos()
    {
        return $this->hasMany(BookingCombo::class, 'booking_id', 'booking_id');
    }

    public function bookingVouchers()
    {
        return $this->hasMany(BookingVoucher::class, 'booking_id', 'booking_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id', 'voucher_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'booking_id', 'booking_id');
    }

    protected static function booted()
    {
        static::updated(function ($booking) {
            if ($booking->isDirty('booking_status') && in_array($booking->booking_status, ['cancelled', 'cancelling'])) {
                event(new \App\Events\BookingCancelled($booking));
            }
        });
    }
}
