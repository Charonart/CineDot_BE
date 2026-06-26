<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoucherRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $voucherId = $this->route('voucher');

        return [
            'code'               => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code')->ignore($voucherId, 'voucher_id'),
            ],
            'discount_type'      => 'nullable|string|in:fixed,percent',
            'discount_value'     => 'nullable|integer|min:1',
            'min_order_value'    => 'nullable|integer|min:0',
            'max_discount_value' => 'nullable|integer|min:1',
            'valid_from'         => 'nullable|date',
            'valid_until'        => 'nullable|date|after:valid_from',
            'usage_limit'        => 'nullable|integer|min:1',
            'is_active'          => 'nullable|boolean',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code)
            ]);
        }
    }
}
