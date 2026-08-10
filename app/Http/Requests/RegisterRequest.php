<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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

        if (!$this->has('username')) {
            $email = $this->input('email', '');
            $baseUsername = strstr($email, '@', true) ?: 'user' . rand(100, 999);
            $this->merge(['username' => $baseUsername]);
        }
    }

    public function rules(): array
    {
        return [
            'name'     => ['nullable', 'string', 'max:100'],
            'fullname' => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'username' => ['nullable', 'string', 'max:50'],
            'phone'    => ['nullable', 'string', 'max:20'],
        ];
    }
}
