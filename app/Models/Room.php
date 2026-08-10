<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $primaryKey = 'room_id';
    public $timestamps = false;

    protected $fillable = [
        'cinema_id',
        'room_name',
        'room_type',
        'seat_matrix',
        'total_seats',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'total_seats' => 'integer',
        'seat_matrix' => 'array',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'room_id', 'room_id');
    }
}
