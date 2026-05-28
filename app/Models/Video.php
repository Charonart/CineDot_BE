<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $primaryKey = 'video_id';
    public $timestamps = false;

    protected $fillable = [
        'movie_id',
        'name',
        'key_value',
        'site',
        'size',
        'type',
        'official',
        'published_at',
        'iso_639_1',
        'iso_3166_1',
    ];

    protected $casts = [
        'size'         => 'integer',
        'official'     => 'boolean',
        'published_at' => 'datetime',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
