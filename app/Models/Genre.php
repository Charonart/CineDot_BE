<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Genre extends Model
{
    use HasFactory;

    protected $primaryKey = 'genre_id';
    public $timestamps = false;

    protected $fillable = [
        'genre_name',
    ];

    /**
     * Accessor: tự generate slug từ genre_name
     */
    public function getSlugAttribute()
    {
        return Str::slug($this->genre_name);
    }

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_genres', 'genre_id', 'movie_id');
    }
}
