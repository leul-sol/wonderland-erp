<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['sometimes', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'source_module' => ['required', 'in:s2,s3,manual'],
            'source_reference' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required_without:lines.*.account_code', 'integer', 'exists:accounts,id'],
            'lines.*.account_code' => ['required_without:lines.*.account_id', 'string', 'max:20'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
