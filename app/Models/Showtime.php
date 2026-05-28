<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'cinema_id',
        'show_date',
        'start_time',
        'end_time',
        'screen',
        'format',
        'price',
        'available_seats',
    ];

    protected $casts = [
        'show_date'       => 'date:Y-m-d',
        'price'           => 'integer',
        'available_seats' => 'integer',
    ];

    /**
     * Showtime thuộc về 1 phim
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Showtime thuộc về 1 rạp
     */
    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }
}
