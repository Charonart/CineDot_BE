<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
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
            'room_name'   => 'required|string|max:255',
            'room_type'   => 'nullable|string|max:50',
            'total_seats' => 'nullable|integer|min:0',
            'seat_matrix' => 'nullable|array',
            'is_active'   => 'boolean',
        ];
    }
}
