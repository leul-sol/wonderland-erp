<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'monthly_repayment' => ['required', 'numeric', 'min:1'],
            'disbursed_at' => ['nullable', 'date'],
        ];
    }
}
