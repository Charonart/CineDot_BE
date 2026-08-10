<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    protected $primaryKey = 'booking_seat_id';
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'showtime_seat_id',
        'ticket_type',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function showtimeSeat()
    {
        return $this->belongsTo(ShowtimeSeat::class, 'showtime_seat_id', 'showtime_seat_id');
    }
}
