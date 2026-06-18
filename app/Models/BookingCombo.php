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

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id', 'combo_id');
    }
}
