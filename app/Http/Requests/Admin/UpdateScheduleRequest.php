<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Schedule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id'       => 'sometimes|integer|exists:movies,id',
            'room_id'        => 'sometimes|integer|exists:rooms,room_id',
            'schedule_date'  => 'sometimes|date|date_format:Y-m-d',
            'schedule_start' => 'sometimes|date_format:H:i',
            'schedule_end'   => 'sometimes|date_format:H:i',
            'base_price'     => 'sometimes|integer|min:0',
        ];
    }

    /**
     * Validate không trùng thời gian (trừ chính nó).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $schedule = $this->route('schedule');
                $scheduleId = is_object($schedule) ? $schedule->schedule_id : $schedule;

                $data = $this->validated();

                // Chỉ check overlap nếu có thay đổi thời gian hoặc phòng
                $hasTimeChange = isset($data['schedule_start']) || isset($data['schedule_end'])
                    || isset($data['schedule_date']) || isset($data['room_id']);

                if (!$hasTimeChange) {
                    return;
                }

                // Lấy schedule hiện tại để merge dữ liệu
                $current = Schedule::findOrFail($scheduleId);

                $roomId = $data['room_id'] ?? $current->room_id;
                $date = $data['schedule_date'] ?? $current->schedule_date->format('Y-m-d');
                $start = $data['schedule_start'] ?? $current->schedule_start;
                $end = $data['schedule_end'] ?? $current->schedule_end;

                $overlap = Schedule::where('room_id', $roomId)
                    ->where('schedule_date', $date)
                    ->where('schedule_id', '!=', $scheduleId)
                    ->where(function ($query) use ($start, $end) {
                        $query->where('schedule_start', '<', $end)
                              ->where('schedule_end', '>', $start);
                    })
                    ->exists();

                if ($overlap) {
                    $validator->errors()->add(
                        'schedule_start',
                        'Suất chiếu bị trùng thời gian với suất khác trong cùng phòng.'
                    );
                }
            }
        ];
    }

    public function messages(): array
    {
        return [
            'movie_id.exists'     => 'Phim không tồn tại.',
            'room_id.exists'      => 'Phòng chiếu không tồn tại.',
            'base_price.min'      => 'Giá cơ bản không được âm.',
        ];
    }
}
