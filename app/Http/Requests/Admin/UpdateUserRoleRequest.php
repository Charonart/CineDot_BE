<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is already handled by role:admin middleware on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|in:admin,staff,customer',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Trường role là bắt buộc.',
            'role.in'       => 'Role phải là một trong: admin, staff, customer.',
        ];
    }
}
