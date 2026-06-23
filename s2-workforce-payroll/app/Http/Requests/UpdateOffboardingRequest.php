<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOffboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clearance_status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed'])],
            'notes' => ['nullable', 'string'],
            'severance_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
