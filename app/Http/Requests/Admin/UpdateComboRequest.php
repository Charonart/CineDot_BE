<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComboRequest extends FormRequest
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
        $comboId = $this->route('combo');

        return [
            'name'        => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('combos', 'name')->ignore($comboId, 'combo_id'),
            ],
            'description' => 'nullable|string',
            'price'       => 'nullable|integer|min:0',
            'image_url'   => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
