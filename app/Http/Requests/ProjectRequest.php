<?php

namespace App\Http\Requests;

use App\Support\UsStates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('project')?->id;

        return [
            'project_number' => ['required', 'string', 'max:50', Rule::unique('projects', 'project_number')->ignore($projectId)],
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'exists:customers,id'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', Rule::in(UsStates::abbreviations())],
            'zip' => ['nullable', 'string', 'max:20'],
            'start_date' => ['nullable', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'on_hold', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:30720', 'mimes:pdf,dwg,dxf,png,jpg,jpeg,doc,docx,xls,xlsx,csv,txt'],
        ];
    }
}
