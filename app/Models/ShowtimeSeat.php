<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowtimeSeat extends Model
{
    protected $primaryKey = 'showtime_seat_id';
    public $timestamps = false;

    protected $fillable = [
        'showtime_id',
        'seat_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

    public function seatType()
    {
        return $this->hasOneThrough(
            SeatType::class,
            Seat::class,
            'seat_id',    // Foreign key on seats table
            'seat_type',  // Foreign key on seat_types table
            'seat_id',    // Local key on showtime_seats table
            'seat_type'   // Local key on seats table
        );
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class, 'showtime_seat_id', 'showtime_seat_id');
    }

    // Dynamic Accessors for backwards compatibility
    public function getRowNameAttribute()
    {
        return $this->seat?->row_name;
    }

    public function getSeatNumberAttribute()
    {
        return $this->seat?->seat_number;
    }

    public function getSeatTypeAttribute()
    {
        return $this->seat?->seat_type;
    }

    public function getSeatCodeAttribute()
    {
        return $this->seat?->seat_code;
    }
}
