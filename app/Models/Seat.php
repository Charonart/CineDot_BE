<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seat extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'seat_id';
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'seat_row',
        'seat_number',
        'seat_type',
        'position_x',
        'position_y',
        'angle',
        'surcharge',
        'is_active',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'position_x'  => 'integer',
        'position_y'  => 'integer',
        'angle'       => 'decimal:2',
        'surcharge'   => 'integer',
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
