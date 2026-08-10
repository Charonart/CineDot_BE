<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('fullname') && $this->has('name')) {
            $this->merge(['fullname' => $this->input('name')]);
        }
        if (!$this->has('avatar') && $this->has('avatar_url')) {
            $this->merge(['avatar' => $this->input('avatar_url')]);
        }
        if (!$this->has('birthday') && $this->has('date_of_birth')) {
            $this->merge(['birthday' => $this->input('date_of_birth')]);
        }
    }

    public function rules(): array
    {
        return [
            'name'          => ['nullable', 'string', 'max:100'],
            'fullname'      => ['nullable', 'string', 'max:100'],
            'avatar'        => ['nullable', 'string', 'max:255'],
            'avatar_url'    => ['nullable', 'string', 'max:255'],
            'birthday'      => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date'],
            'gender'        => ['nullable', 'string', 'in:male,female,other'],
            'province_id'   => ['nullable', 'integer', 'exists:provinces,province_id'],
            'phone'         => ['nullable', 'string', 'max:20'],
        ];
    }
}
