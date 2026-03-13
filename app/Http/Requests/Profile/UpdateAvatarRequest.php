<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', File::image()->max(2 * 1024)],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Choose an image to use as your avatar.',
        ];
    }
}
