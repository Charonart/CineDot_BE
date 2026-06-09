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
            'province' => ['nullable', 'string', 'max:100'],
        ];
    }
}
