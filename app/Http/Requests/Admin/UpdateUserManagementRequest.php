<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'role' => ['required', Rule::in(['admin', 'manager', 'detailer', 'viewer'])],
            'title' => ['nullable', 'string', 'max:100'],
            'active' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
