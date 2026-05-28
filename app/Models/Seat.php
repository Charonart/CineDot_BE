<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $primaryKey = 'seat_id';
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'seat_row',
        'seat_number',
        'seat_type',
        'position_x',
        'position_y',
        'is_active',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'position_x'  => 'integer',
        'position_y'  => 'integer',
        'is_active'   => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function scheduleSeats()
    {
        return $this->hasMany(ScheduleSeat::class, 'seat_id', 'seat_id');
    }
}
