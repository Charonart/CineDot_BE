<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->person_id,
            'tmdbPersonId'       => $this->tmdb_person_id,
            'name'                 => $this->name,
            'originalName'        => $this->original_name,
            'gender'               => $this->gender,
            'profilePath'         => $this->profile_path,
            'adult'                => $this->adult,
            'popularity'           => $this->popularity,
            'knownForDepartment' => $this->known_for_department,
            'biography'            => $this->biography,
            'birthday'             => $this->birthday ? $this->birthday->toDateString() : null,
            'deathday'             => $this->deathday ? $this->deathday->toDateString() : null,
            'placeOfBirth'       => $this->place_of_birth,
            'imdbId'              => $this->imdb_id,
            'homepage'             => $this->homepage,
            'createdAt'          => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt'          => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
