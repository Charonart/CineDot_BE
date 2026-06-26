<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $genreId = $this->route('genre');

        return [
            'genre_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'genre_name')->ignore($genreId, 'genre_id'),
            ],
        ];
    }
}
