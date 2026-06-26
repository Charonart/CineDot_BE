<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'user_id',
        'schedule_id',
        'voucher_id',
        'total_amount',
        'discount_amount',
        'booking_status',
        'booking_code',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'discount_amount' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
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
}
