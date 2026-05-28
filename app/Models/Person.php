<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $table = 'persons';
    protected $primaryKey = 'person_id';

    protected $fillable = [
        'tmdb_person_id',
        'name',
        'original_name',
        'gender',
        'profile_path',
        'adult',
        'popularity',
        'known_for_department',
        'biography',
        'birthday',
        'deathday',
        'place_of_birth',
        'imdb_id',
        'homepage',
    ];

    protected $casts = [
        'adult'      => 'boolean',
        'popularity' => 'decimal:3',
        'gender'     => 'integer',
        'birthday'   => 'date',
        'deathday'   => 'date',
    ];

    /** Tất cả credits (cast + crew) */
    public function credits()
    {
        return $this->hasMany(Credit::class, 'person_id', 'person_id');
    }

    /** Phim mà người này đóng (cast) */
    public function castCredits()
    {
        return $this->hasMany(Credit::class, 'person_id', 'person_id')
                    ->where('credit_type', 'cast');
    }

    /** Phim mà người này làm crew */
    public function crewCredits()
    {
        return $this->hasMany(Credit::class, 'person_id', 'person_id')
                    ->where('credit_type', 'crew');
    }
}
