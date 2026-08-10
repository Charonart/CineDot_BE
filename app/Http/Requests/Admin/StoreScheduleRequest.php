<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Schedule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id'       => 'required|integer|exists:movies,id',
            'room_id'        => 'required|integer|exists:rooms,room_id',
            'schedule_date'  => 'required|date|date_format:Y-m-d',
            'schedule_start' => 'required|date_format:H:i',
            'schedule_end'   => 'required|date_format:H:i|after:schedule_start',
            'base_price'     => 'required|integer|min:0',
        ];
    }

    /**
     * Validate không trùng thời gian cho cùng phòng trong cùng ngày.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $data = $this->validated();

                $overlap = Schedule::where('room_id', $data['room_id'])
                    ->where('schedule_date', $data['schedule_date'])
                    ->where(function ($query) use ($data) {
                        $query->where(function ($q) use ($data) {
                            // Suất mới bắt đầu trong khoảng suất cũ
                            $q->where('schedule_start', '<', $data['schedule_end'])
                              ->where('schedule_end', '>', $data['schedule_start']);
                        });
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
            'movie_id.required'       => 'Vui lòng chọn phim.',
            'movie_id.exists'         => 'Phim không tồn tại.',
            'room_id.required'        => 'Vui lòng chọn phòng chiếu.',
            'room_id.exists'          => 'Phòng chiếu không tồn tại.',
            'schedule_date.required'  => 'Vui lòng chọn ngày chiếu.',
            'schedule_start.required' => 'Vui lòng nhập giờ bắt đầu.',
            'schedule_end.required'   => 'Vui lòng nhập giờ kết thúc.',
            'schedule_end.after'      => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'base_price.required'     => 'Vui lòng nhập giá cơ bản.',
            'base_price.min'          => 'Giá cơ bản không được âm.',
        ];
    }
}
