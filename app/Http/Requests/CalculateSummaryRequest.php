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
        $rawShowtimeId = $this->input('showtime_id', $this->input('schedule_id'));
        if ($rawShowtimeId !== null) {
            $cleanShowtimeId = (int) str_replace('showtime-', '', (string) $rawShowtimeId);
            $this->merge(['showtime_id' => $cleanShowtimeId]);
        }

        $seatIds = $this->input('showtime_seat_ids', $this->input('schedule_seat_ids'));
        if (!empty($seatIds)) {
            $this->merge(['showtime_seat_ids' => (array) $seatIds]);
        } else {
            $showtimeId = $this->input('showtime_id');
            $seatCodes = $this->input('seats') ?? $this->input('seat_codes');
            if ($seatCodes && $showtimeId) {
                $codeArray = is_array($seatCodes) ? $seatCodes : explode(',', (string) $seatCodes);
                $codeArray = array_filter(array_map('trim', $codeArray));
                if (!empty($codeArray)) {
                    // Ensure seats are generated in DB
                    try {
                        app(\App\Services\SeatService::class)->getScheduleSeats((int) $showtimeId);
                    } catch (\Exception $e) {
                        // ignore
                    }

                    $foundIds = \App\Models\ShowtimeSeat::where('showtime_id', $showtimeId)
                        ->where(function ($q) use ($codeArray) {
                            foreach ($codeArray as $code) {
                                if (preg_match('/^([A-Za-z]+)(\d+)$/', $code, $m)) {
                                    $q->orWhere(function ($sq) use ($m) {
                                        $sq->where('row_name', strtoupper($m[1]))
                                           ->where('seat_number', $m[2]);
                                    });
                                }
                            }
                        })
                        ->pluck('showtime_seat_id')
                        ->toArray();

                    if (!empty($foundIds)) {
                        $this->merge(['showtime_seat_ids' => $foundIds]);
                    }
                }
            }
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
