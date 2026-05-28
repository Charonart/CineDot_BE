<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $primaryKey = 'credit_id';
    public $timestamps = false;

    protected $fillable = [
        'movie_id',
        'person_id',
        'credit_type',
        'character_name',
        'department',
        'job',
        'order',
        'credit_original_id',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id', 'person_id');
    }
}
