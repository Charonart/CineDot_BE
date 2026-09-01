<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_name'        => 'sometimes|required|string|max:255',
            'room_type'        => 'nullable|string|max:100',
            'screen_type'      => 'nullable|string|in:standard_2d,standard_3d,imax_laser,screenx,dolby_cinema,onyx_led',
            'sound_technology' => 'nullable|string|in:surround_71,dolby_atmos,imax_sound',
            'screen_config'    => 'nullable|array',
            'features'         => 'nullable|array',
            'total_seats'      => 'nullable|integer|min:0',
            'seats'            => 'nullable|array',
            'seat_matrix'      => 'nullable|array',
            'is_active'        => 'nullable|boolean',
        ];
    }
}
