<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCinemasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'          => ['nullable', 'string', 'max:50'],
            'province_code' => ['nullable', 'string', 'max:50'],
            'province_id'   => ['nullable', 'integer', 'exists:provinces,province_id'],
            'province'      => ['nullable', 'string', 'max:100'],
            'city'          => ['nullable', 'string', 'max:100'],
        ];
    }
}
