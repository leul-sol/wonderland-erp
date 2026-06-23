<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOffboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(['resignation', 'termination', 'retirement', 'end_of_contract', 'death'])],
            'last_working_day' => ['required', 'date'],
            'initiated_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'calculate_severance' => ['nullable', 'boolean'],
            'archive_now' => ['nullable', 'boolean'],
        ];
    }
}
