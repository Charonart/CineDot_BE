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
            'id'          => $this->id,
            'slug'        => $this->slug,
            'title'       => $this->title,
            'posterUrl'   => $this->poster_path,
            'backdropUrl' => $this->backdrop_path,
            'releaseDate' => $this->release_date,
            'runtime'     => $this->duration_minutes,
            'rating'      => isset($this->reviews_avg_rating) ? round((float) $this->reviews_avg_rating, 1) : null,
            'voteCount'   => $this->reviews_count ?? 0,
            'genres'      => $this->whenLoaded('genres', function () {
                return $this->genres->map(fn($g) => [
                    'id'   => $g->genre_id,
                    'name' => $g->genre_name,
                ])->values();
            }, []),
        ];
    }
}
