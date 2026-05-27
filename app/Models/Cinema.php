<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'chain',
        'city',
        'address',
        'slug',
        'screen_count',
    ];

    /**
     * Một rạp có nhiều lịch chiếu
     */
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

    /**
     * Các phim đang chiếu tại rạp này (qua showtimes)
     */
    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'showtimes')
                    ->withPivot('show_date', 'start_time', 'end_time', 'screen', 'format', 'price', 'available_seats');
    }
}
