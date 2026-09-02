<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieDetailResource extends JsonResource
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
            'age_rating'       => $this->age_rating,
            'ageRating'        => $this->age_rating,
            'ageInfo'          => $this->age_info,
            'vote_average'     => $this->vote_average ? (float) $this->vote_average : 0.0,
            'vote_count'       => $this->vote_count ? (int) $this->vote_count : 0,
            'rating'           => $this->vote_average ? (float) $this->vote_average : null,
            'voteCount'        => $this->vote_count ? (int) $this->vote_count : 0,
            'imdb_id'          => $this->imdb_id,
            'imdb_url'         => $this->imdb_url,
            'imdbId'           => $this->imdb_id,
            'imdbUrl'          => $this->imdb_url,
            'genres'           => $this->whenLoaded('genres', function () {
                return $this->genres->map(fn($g) => [
                    'id'   => $g->genre_id,
                    'name' => $g->genre_name,
                ])->values();
            }, []),
            'cast'             => $this->whenLoaded('castCredits', function () {
                return $this->castCredits->map(fn($c) => [
                    'creditId'    => $c->credit_id,
                    'personId'    => $c->person_id,
                    'name'        => $c->person ? $c->person->name : null,
                    'character'   => $c->character_name,
                    'profilePath' => $c->person ? $c->person->profile_path : null,
                    'order'       => $c->order,
                ])->values();
            }, []),
            'crew'             => $this->whenLoaded('crewCredits', function () {
                return $this->crewCredits->map(fn($c) => [
                    'creditId'    => $c->credit_id,
                    'personId'    => $c->person_id,
                    'name'        => $c->person ? $c->person->name : null,
                    'job'         => $c->job,
                    'department'  => $c->department,
                    'profilePath' => $c->person ? $c->person->profile_path : null,
                ])->values();
            }, []),
            'videos'           => $this->whenLoaded('videos', function () {
                return $this->videos->map(fn($v) => [
                    'videoId'  => $v->video_id,
                    'name'     => $v->name,
                    'key'      => $v->key_value,
                    'site'     => $v->site,
                    'type'     => $v->type,
                    'official' => (bool) $v->official,
                ])->values();
            }, []),
        ];
    }
}
