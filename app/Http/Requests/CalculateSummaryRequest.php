<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('showtime_id') && $this->has('schedule_id')) {
            $this->merge(['showtime_id' => $this->input('schedule_id')]);
        }
        if (!$this->has('showtime_seat_ids') && $this->has('schedule_seat_ids')) {
            $this->merge(['showtime_seat_ids' => $this->input('schedule_seat_ids')]);
        }
    }

    public function rules(): array
    {
        return [
            'showtime_id'         => ['required', 'integer', 'exists:showtimes,showtime_id'],
            'showtime_seat_ids'   => ['required', 'array', 'min:1', 'max:8'],
            'showtime_seat_ids.*' => ['integer', 'exists:showtime_seats,showtime_seat_id'],
            'combos'              => ['nullable', 'array'],
            'combos.*.combo_id'   => ['required_with:combos', 'integer', 'exists:combos,combo_id'],
            'combos.*.quantity'   => ['required_with:combos', 'integer', 'min:1'],
            'voucher_code'        => ['nullable', 'string'],
            'points_used'         => ['nullable', 'integer', 'min:0'],
        ];
    }
}
