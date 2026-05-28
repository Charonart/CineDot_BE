<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSeat extends Model
{
    protected $primaryKey = 'schedule_seat_id';
    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'seat_id',
        'status',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class, 'schedule_seat_id', 'schedule_seat_id');
    }
}
