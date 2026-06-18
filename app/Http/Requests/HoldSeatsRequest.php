<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id'         => ['required', 'integer', 'exists:schedules,schedule_id'],
            'schedule_seat_ids'   => ['required', 'array', 'min:1', 'max:8'],
            'schedule_seat_ids.*' => ['integer', 'exists:schedule_seats,schedule_seat_id'],
            'combos'              => ['nullable', 'array'],
            'combos.*.combo_id'   => ['required_with:combos', 'integer', 'exists:combos,combo_id'],
            'combos.*.quantity'   => ['required_with:combos', 'integer', 'min:1'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'schedule_seat_ids.max' => 'Bạn chỉ được đặt tối đa 8 ghế cho mỗi giao dịch.',
        ];
    }
}
