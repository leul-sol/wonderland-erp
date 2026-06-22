<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'sub_type' => ['nullable', 'string', 'max:60'],
            'normal_balance' => ['sometimes', Rule::in(['debit', 'credit'])],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ];
    }
}
