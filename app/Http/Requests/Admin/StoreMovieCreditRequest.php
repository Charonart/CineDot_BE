<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovieCreditRequest extends FormRequest
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
            'person_id'      => 'required|exists:persons,id',
            'credit_type'    => 'required|in:cast,crew',
            'character_name' => 'nullable|string|max:255',
            'order'          => 'nullable|integer|min:0',
        ];
    }
}
