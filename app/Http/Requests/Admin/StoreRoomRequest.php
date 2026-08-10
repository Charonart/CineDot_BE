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
            'room_name' => 'required|string|max:255',
            'room_type' => 'required|string|in:standard,vip,couple',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'room_type.required' => 'Vui lòng chọn loại phòng.',
            'room_type.in'       => 'Loại phòng phải là: standard, vip, hoặc couple.',
        ];
    }
}

