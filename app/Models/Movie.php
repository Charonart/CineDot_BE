<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'poster_url',
        'trailer_url',
        'status',
        'duration',
        'release_date'
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre');
    }
}
