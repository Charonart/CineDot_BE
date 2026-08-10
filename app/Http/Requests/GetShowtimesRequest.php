<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetShowtimesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'       => ['nullable', 'date_format:Y-m-d'],
            'cinema_id'  => ['nullable', 'integer', 'exists:cinemas,cinema_id'],
            'movie_id'   => ['nullable'],
            'movie_slug' => ['nullable', 'string'],
            'movie'      => ['nullable', 'string'],
            'province'   => ['nullable', 'string', 'max:100'],
        ];
    }
}
