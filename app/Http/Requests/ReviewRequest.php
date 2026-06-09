<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating'  => ['required', 'numeric', 'min:1', 'max:10'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
