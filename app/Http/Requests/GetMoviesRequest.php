<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMoviesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'limit'    => ['nullable', 'integer', 'min:1', 'max:50'],
            'status'   => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'search'   => ['nullable', 'string', 'max:100'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,genre_id'],
            'sort'     => ['nullable', 'string'],
        ];
    }
}
