<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seat extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'seat_id';

    protected $fillable = [
        'room_id',
        'seat_type',
        'row_name',
        'seat_number',
        'row_index',
        'col_index',
        'coord_x',
        'coord_y',
        'angle',
        'couple_partner_id',
        'is_active',
    ];

    protected $casts = [
        'row_index' => 'integer',
        'col_index' => 'integer',
        'coord_x'   => 'integer',
        'coord_y'   => 'integer',
        'angle'     => 'integer',
        'is_active' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function seatType()
    {
        return $this->belongsTo(SeatType::class, 'seat_type', 'seat_type');
    }

    public function couplePartner()
    {
        return $this->belongsTo(Seat::class, 'couple_partner_id', 'seat_id');
    }

    public function showtimeSeats()
    {
        return $this->hasMany(ShowtimeSeat::class, 'seat_id', 'seat_id');
    }

    public function getSeatCodeAttribute(): string
    {
        return $this->row_name . $this->seat_number;
    }
}
