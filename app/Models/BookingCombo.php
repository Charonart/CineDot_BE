<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCombo extends Model
{
    protected $primaryKey = 'booking_combo_id';

    protected $fillable = [
        'booking_id',
        'combo_id',
        'quantity',
        'price_at_booking',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'price_at_booking' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id', 'combo_id');
    }
}
