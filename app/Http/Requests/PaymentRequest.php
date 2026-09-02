<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id'     => ['nullable'],
            'booking_code'   => ['nullable', 'string'],
            'showtime_id'    => ['nullable'],
            'payment_method' => ['nullable', 'string'],
            'amount'         => ['nullable', 'numeric', 'min:0'],
            'combos'         => ['nullable', 'array'],
            'voucher_code'   => ['nullable', 'string'],
        ];
    }
}
