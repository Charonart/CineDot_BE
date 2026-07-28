<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowtimeSeat extends Model
{
    protected $primaryKey = 'showtime_seat_id';
    public $timestamps = false;

    protected $fillable = [
        'showtime_id',
        'row_name',
        'seat_number',
        'seat_type',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    public function seatType()
    {
        return $this->belongsTo(SeatType::class, 'seat_type', 'seat_type');
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class, 'showtime_seat_id', 'showtime_seat_id');
    }
}
