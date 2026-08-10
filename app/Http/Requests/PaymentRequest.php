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
            'booking_id'     => ['required', 'integer', 'exists:bookings,booking_id'],
            'payment_method' => ['nullable', 'string', 'in:MOMO,VNPAY,ZALOPAY,CASH,CREDIT_CARD'],
            'amount'         => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
