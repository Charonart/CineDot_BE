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
            'campaign_id' => 'nullable|exists:campaigns,campaign_id',
            'title'       => 'nullable|string|max:255',
            'image_url'   => 'nullable|string|max:500',
            'link_url'    => 'nullable|string|max:500',
            'order'       => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
