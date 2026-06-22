<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'room_id';
    public $timestamps = false;

    protected $fillable = [
        'cinema_id',
        'room_name',
        'room_type',
        'total_seats',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'total_seats'  => 'integer',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class, 'room_id', 'room_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'room_id', 'room_id');
    }
}
