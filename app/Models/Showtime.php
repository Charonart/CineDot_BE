<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    protected $primaryKey = 'showtime_id';

    protected $fillable = [
        'room_id',
        'movie_id',
        'showtime_start',
        'showtime_end',
        'base_price',
    ];

    protected $casts = [
        'showtime_start' => 'datetime',
        'showtime_end'   => 'datetime',
        'base_price'     => 'decimal:2',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }

    public function showtimeSeats()
    {
        return $this->hasMany(ShowtimeSeat::class, 'showtime_id', 'showtime_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'showtime_id', 'showtime_id');
    }

    /**
     * Lấy rạp chiếu qua phòng
     */
    public function cinema()
    {
        return $this->hasOneThrough(
            Cinema::class,
            Room::class,
            'room_id',    // FK trên rooms
            'cinema_id',  // FK trên cinemas
            'room_id',    // local key trên showtimes
            'cinema_id'   // local key trên rooms
        );
    }
}
