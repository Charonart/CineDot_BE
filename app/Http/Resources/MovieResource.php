<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->movie_id,
            'slug'             => $this->slug,
            'title'            => $this->title,
            'originalTitle'    => $this->original_title,
            'overview'         => $this->overview,
            'status'           => $this->status,
            'posterUrl'        => $this->poster_path,
            'backdropUrl'      => $this->backdrop_path,
            'releaseDate'      => is_object($this->release_date) ? $this->release_date->format('Y-m-d') : $this->release_date,
            'runtime'          => $this->duration,
            'originalLanguage' => $this->original_language,
            'popularity'       => $this->popularity ? (float) $this->popularity : 0,
            'adult'            => (bool) $this->adult,
            'rating'           => null,
            'voteCount'        => 0,
            'genres'           => $this->whenLoaded('genres', function () {
                return $this->genres->map(fn($g) => [
                    'id'   => $g->genre_id,
                    'name' => $g->genre_name,
                ])->values();
            }, []),
            'trailerUrl'       => $this->relationLoaded('videos') ? (
                (($trailer = $this->videos->firstWhere('type', 'Trailer') ?? $this->videos->first()) && !empty($trailer->key_value))
                    ? "https://www.youtube.com/watch?v={$trailer->key_value}"
                    : null
            ) : null,
            'supportedFormats' => $this->supported_formats ?? [],
        ];
    }
}
