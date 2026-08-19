<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCinemaRequest extends FormRequest
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
            'cinema_name'    => 'sometimes|required|string|max:255',
            'slug'           => 'nullable|string|max:255',
            'cinema_address' => 'nullable|string|max:255',
            'province_id'    => 'nullable|exists:provinces,province_id',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'description'    => 'nullable|string',
            'is_active'      => 'boolean',
        ];
    }
}
