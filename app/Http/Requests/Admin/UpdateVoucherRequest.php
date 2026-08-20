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
            'campaign_id'        => 'nullable|exists:campaigns,campaign_id',
            'code'               => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code')->ignore($voucherId, 'voucher_id'),
            ],
            'title'              => 'nullable|string|max:150',
            'description'        => 'nullable|string',
            'voucher_type'       => 'nullable|string|in:ticket,combo,order,all',
            'discount_type'      => 'nullable|string|in:fixed_amount,percentage,fixed,percent',
            'discount_value'     => 'nullable|numeric|min:0',
            'min_order_value'    => 'nullable|numeric|min:0',
            'max_discount_value' => 'nullable|numeric|min:0',
            'system_limit'       => 'nullable|integer|min:1',
            'usage_limit'        => 'nullable|integer|min:1',
            'limit_per_user'     => 'nullable|integer|min:1',
            'valid_from'         => 'nullable|date',
            'valid_until'        => 'nullable|date|after_or_equal:valid_from',
            'rules_engine'       => 'nullable|array',
            'is_active'          => 'nullable|boolean',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $merge = [];
        if ($this->has('code')) {
            $merge['code'] = strtoupper($this->code);
        }
        if ($this->has('discount_type')) {
            if ($this->discount_type === 'fixed') {
                $merge['discount_type'] = 'fixed_amount';
            } elseif ($this->discount_type === 'percent') {
                $merge['discount_type'] = 'percentage';
            }
        }
        if ($this->has('usage_limit') && !$this->has('system_limit')) {
            $merge['system_limit'] = $this->usage_limit;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
