<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeatRequest extends FormRequest
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
            'seat_row'    => 'required|string|max:5',
            'seat_number' => 'required|integer|min:1',
            'seat_type'   => 'nullable|string|max:50',
            'position_x'  => 'nullable|integer',
            'position_y'  => 'nullable|integer',
            'is_active'   => 'boolean',
        ];
    }
}
