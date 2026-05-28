<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'movie_id',
        'room_id',
        'schedule_date',
        'schedule_start',
        'schedule_end',
        'base_price',
    ];

    protected $casts = [
        'schedule_date' => 'date:Y-m-d',
        'base_price'    => 'integer',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function scheduleSeats()
    {
        return $this->hasMany(ScheduleSeat::class, 'schedule_id', 'schedule_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'schedule_id', 'schedule_id');
    }

    /**
     * Lấy cinema qua room
     */
    public function cinema()
    {
        return $this->hasOneThrough(
            Cinema::class,
            Room::class,
            'room_id',      // FK on rooms
            'cinema_id',    // FK on cinemas
            'room_id',      // local key on schedules
            'cinema_id'     // local key on rooms
        );
    }
}
