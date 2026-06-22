<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
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
            'room_name'   => 'sometimes|required|string|max:255',
            'room_type'   => 'nullable|string|max:50',
            'total_seats' => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ];
    }
}
