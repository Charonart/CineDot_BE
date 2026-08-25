<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RevenueReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d' . ($this->filled('start_date') ? '|after_or_equal:start_date' : ''),
            'from_date'  => 'nullable|date_format:Y-m-d',
            'to_date'    => 'nullable|date_format:Y-m-d' . ($this->filled('from_date') ? '|after_or_equal:from_date' : ''),
            'cinema_id'  => 'nullable|integer|exists:cinemas,cinema_id',
            'movie_id'   => 'nullable|integer|exists:movies,movie_id',
            'group_by'   => 'nullable|string|in:day',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date_format'   => 'Định dạng start_date không hợp lệ (chuẩn Y-m-d).',
            'end_date.date_format'     => 'Định dạng end_date không hợp lệ (chuẩn Y-m-d).',
            'end_date.after_or_equal'  => 'Ngày kết thúc (end_date) phải lớn hơn hoặc bằng ngày bắt đầu (start_date).',
            'from_date.date_format'    => 'Định dạng from_date không hợp lệ (chuẩn Y-m-d).',
            'to_date.date_format'      => 'Định dạng to_date không hợp lệ (chuẩn Y-m-d).',
            'to_date.after_or_equal'   => 'Ngày kết thúc (to_date) phải lớn hơn hoặc bằng ngày bắt đầu (from_date).',
            'cinema_id.exists'         => 'Rạp được chọn không tồn tại trong hệ thống.',
            'movie_id.exists'          => 'Phim được chọn không tồn tại trong hệ thống.',
            'group_by.in'              => 'group_by hiện chỉ hỗ trợ giá trị "day".',
        ];
    }
}
