<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
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
        return [
            'code'               => 'required|string|unique:vouchers,code|max:50',
            'discount_type'      => 'required|string|in:fixed,percent',
            'discount_value'     => 'required|integer|min:1',
            'min_order_value'    => 'nullable|integer|min:0',
            'max_discount_value' => 'nullable|integer|min:1',
            'valid_from'         => 'required|date',
            'valid_until'        => 'required|date|after:valid_from',
            'usage_limit'        => 'nullable|integer|min:1',
            'is_active'          => 'boolean',
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
