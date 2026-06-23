<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['sometimes', 'numeric', 'min:0'],
            'pension_category' => ['nullable', 'in:covered,not_covered'],
            'default_role' => ['nullable', 'string', 'max:50'],
        ];
    }
}
