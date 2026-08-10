<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieRequest extends FormRequest
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
            'title'             => 'sometimes|required|string|max:255',
            'original_title'    => 'nullable|string|max:255',
            'overview'          => 'nullable|string',
            'release_date'      => 'nullable|date',
            'original_language' => 'nullable|string|max:10',
            'adult'             => 'boolean',
            'video'             => 'boolean',
            'popularity'        => 'nullable|numeric|min:0',
            'backdrop_path'     => 'nullable|string|url',
            'poster_path'       => 'nullable|string|url',
            'duration_minutes'  => 'nullable|integer|min:1',
            'status'            => 'sometimes|required|string|in:now_showing,coming_soon,stopped',
            'genre_ids'         => 'nullable|array',
            'genre_ids.*'       => 'exists:genres,genre_id',

            'trailer_url'       => 'nullable|url',
        ];
    }
}
