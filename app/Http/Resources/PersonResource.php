<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->person_id,
            'tmdb_person_id'       => $this->tmdb_person_id,
            'name'                 => $this->name,
            'original_name'        => $this->original_name,
            'gender'               => $this->gender,
            'avatar'               => $this->profile_path,
            'adult'                => $this->adult,
            'popularity'           => $this->popularity,
            'known_for_department' => $this->known_for_department,
            'bio'                  => $this->biography,
            'birthday'             => $this->birthday,
            'deathday'             => $this->deathday,
            'place_of_birth'       => $this->place_of_birth,
            'imdb_id'              => $this->imdb_id,
            'homepage'             => $this->homepage,
            'cast_movies'          => $this->whenLoaded('castCredits', function () {
                return $this->castCredits->map(function ($credit) {
                    return [
                        'movie_id'       => $credit->movie->id ?? null,
                        'slug'           => $credit->movie->slug ?? null,
                        'title'          => $credit->movie->title ?? null,
                        'poster'         => $credit->movie->poster_path ?? null,
                        'character_name' => $credit->character_name,
                        'order'          => $credit->order,
                    ];
                });
            }),
            'crew_movies'          => $this->whenLoaded('crewCredits', function () {
                return $this->crewCredits->map(function ($credit) {
                    return [
                        'movie_id'   => $credit->movie->id ?? null,
                        'slug'       => $credit->movie->slug ?? null,
                        'title'      => $credit->movie->title ?? null,
                        'poster'     => $credit->movie->poster_path ?? null,
                        'job'        => $credit->job,
                        'department' => $credit->department,
                    ];
                });
            }),
        ];
    }
}
