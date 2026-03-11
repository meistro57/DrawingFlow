<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['admin', 'manager', 'detailer', 'viewer'])],
            'active' => ['required', 'boolean'],
        ];
    }
}
