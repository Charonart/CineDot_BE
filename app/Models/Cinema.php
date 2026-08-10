<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $primaryKey = 'cinema_id';

    protected $fillable = [
        'cinema_name',
        'slug',
        'cinema_address',
        'province_id',
        'phone',
        'email',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'cinema_id', 'cinema_id');
    }

    /**
     * Các suất chiếu tại rạp này (qua rooms)
     */
    public function showtimes()
    {
        return $this->hasManyThrough(
            Showtime::class,
            Room::class,
            'cinema_id', // FK trên rooms
            'room_id',   // FK trên showtimes
            'cinema_id', // local key trên cinemas
            'room_id'    // local key trên rooms
        );
    }
}
