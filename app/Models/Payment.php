<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'transaction_id',
        'status',
        'payment_data',
        'paid_at',
    ];

    protected $casts = [
        'amount'       => 'integer',
        'payment_data' => 'json',
        'paid_at'      => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}
