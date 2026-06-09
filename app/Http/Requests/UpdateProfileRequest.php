<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname'    => ['nullable', 'string', 'max:100'],
            'avatar'      => ['nullable', 'string', 'max:255'],
            'birthday'    => ['nullable', 'date'],
            'gender'      => ['nullable', 'string', 'in:male,female,other'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,province_id'],
            'phone'       => ['nullable', 'string', 'max:20'],
        ];
    }
}
