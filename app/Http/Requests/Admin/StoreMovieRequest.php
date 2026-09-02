<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'             => 'required|string|max:255',
            'original_title'    => 'nullable|string|max:255',
            'overview'          => 'nullable|string',
            'release_date'      => 'nullable|date',
            'original_language' => 'nullable|string|max:10',
            'age_rating'        => 'nullable|string|in:P,K,T13,T16,T18,C',
            'popularity'        => 'nullable|numeric|min:0',
            'backdrop_path'     => 'nullable|string',
            'poster_path'       => 'nullable|string',
            'duration_minutes'  => 'nullable|integer|min:1',
            'status'            => 'required|string|in:now_showing,upcoming,ended,coming_soon,stopped',
            'vote_average'      => 'nullable|numeric|between:0,10',
            'vote_count'        => 'nullable|integer|min:0',
            'imdb_id'           => 'nullable|string|max:20',
            'tmdb_id'           => 'nullable|integer',
            'genre_ids'         => 'nullable|array',
            'genre_ids.*'       => 'exists:genres,genre_id',

            'trailer_url'       => 'nullable|url',
        ];
    }
}
