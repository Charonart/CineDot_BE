<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->payment_id,
            'bookingId'     => $this->booking_id,
            'bookingCode'   => $this->booking ? $this->booking->booking_code : null,
            'amount'        => $this->amount,
            'method'        => $this->method,
            'transactionId' => $this->transaction_id,
            'status'        => $this->status,
            'paymentData'   => $this->payment_data,
            'paidAt'        => $this->paid_at ? $this->paid_at->toIso8601String() : null,
            'createdAt'     => $this->created_at ? $this->created_at->toIso8601String() : null,
            'user'          => $this->booking && $this->booking->user ? [
                'id'       => $this->booking->user->user_id,
                'fullname' => $this->booking->user->fullname,
                'email'    => $this->booking->user->email,
            ] : null,
        ];
    }
}
