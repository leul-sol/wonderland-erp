<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisciplinaryRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_type' => ['required', Rule::in([
                'oral_warning',
                'first_written_warning',
                'final_written_warning',
                'suspension',
                'termination',
                'immediate_dismissal',
            ])],
            'reason' => ['required', 'string'],
            'effective_date' => ['required', 'date'],
            'suspension_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}
