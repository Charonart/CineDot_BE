<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatType extends Model
{
    protected $table = 'seat_types';

    /**
     * PK là string (vd: 'standard', 'vip', 'couple')
     */
    protected $primaryKey = 'seat_type';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'seat_type',
        'surcharge_amount',
    ];

    protected $casts = [
        'surcharge_amount' => 'decimal:2',
    ];

    public function showtimeSeats()
    {
        return $this->hasMany(ShowtimeSeat::class, 'seat_type', 'seat_type');
    }
}
