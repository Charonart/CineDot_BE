<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
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
        return [
            'tmdb_person_id' => 'nullable|integer|unique:persons,tmdb_person_id',
            'name' => 'required|string|max:255',
            'original_name' => 'nullable|string|max:255',
            'gender' => 'nullable|integer|in:0,1,2,3',
            'profile_path' => 'nullable|string|max:500',
            'adult' => 'boolean',
            'popularity' => 'nullable|numeric|min:0',
            'known_for_department' => 'nullable|string|max:255',
            'biography' => 'nullable|string',
            'birthday' => 'nullable|date',
            'deathday' => 'nullable|date|after_or_equal:birthday',
            'place_of_birth' => 'nullable|string|max:255',
            'imdb_id' => 'nullable|string|max:50',
            'homepage' => 'nullable|string|max:255',
        ];
    }
}
