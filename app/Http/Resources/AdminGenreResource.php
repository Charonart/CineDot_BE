<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGenreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->genre_id,
            'genre_name'   => $this->genre_name,
            'name'         => $this->genre_name,
            'slug'         => $this->slug,
            'movies_count' => (int) ($this->movies_count ?? 0),
            'moviesCount'  => (int) ($this->movies_count ?? 0),
        ];
    }
}
