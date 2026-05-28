<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $primaryKey = 'cinema_id';
    public $timestamps = false;

    const CREATED_AT = 'create_at';

    protected $fillable = [
        'cinema_name',
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
     * Các lịch chiếu tại rạp này (qua rooms)
     */
    public function schedules()
    {
        return $this->hasManyThrough(
            Schedule::class,
            Room::class,
            'cinema_id',   // FK on rooms
            'room_id',     // FK on schedules
            'cinema_id',   // local key on cinemas
            'room_id'      // local key on rooms
        );
    }
}
