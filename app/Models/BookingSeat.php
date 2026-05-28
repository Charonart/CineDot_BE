<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    protected $primaryKey = 'booking_seat_id';
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'schedule_seat_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function scheduleSeat()
    {
        return $this->belongsTo(ScheduleSeat::class, 'schedule_seat_id', 'schedule_seat_id');
    }
}
