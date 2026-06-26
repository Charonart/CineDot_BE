<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProvinceRequest extends FormRequest
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
        $provinceId = $this->route('province');

        return [
            'province_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('provinces', 'province_name')->ignore($provinceId, 'province_id'),
            ],
            'province_code' => [
                'nullable',
                'string',
                'max:10',
                Rule::unique('provinces', 'province_code')->ignore($provinceId, 'province_id'),
            ],
        ];
    }
}
