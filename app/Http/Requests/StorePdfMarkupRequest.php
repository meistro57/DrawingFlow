<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePdfMarkupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'page_number' => ['required', 'integer', 'min:1'],
            'markup_type' => ['required', 'in:circle,arrow,text,highlight,stamp'],
            'markup_data' => ['required', 'array'],
            'markup_data.x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'markup_data.y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'markup_data.x2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'markup_data.y2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'markup_data.width' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'markup_data.height' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'markup_data.text' => ['nullable', 'string', 'max:500'],
            'markup_data.label' => ['nullable', 'string', 'max:100'],
            'markup_data.color' => ['nullable', 'string', 'max:20'],
            'markup_data.bg_color' => ['nullable', 'string', 'max:20'],
            'markup_data.border_color' => ['nullable', 'string', 'max:20'],
        ];
    }
}
