<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'     => 'nullable|string|max:255',
            'image_url' => 'nullable|url|max:500',
            'link_url'  => 'nullable|url|max:500',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ];
    }
}
