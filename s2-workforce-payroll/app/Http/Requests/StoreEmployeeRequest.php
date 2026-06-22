<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'default_role' => ['nullable', 'string', 'max:50'],
            'hire_date' => ['nullable', 'date'],
        ];
    }
}
