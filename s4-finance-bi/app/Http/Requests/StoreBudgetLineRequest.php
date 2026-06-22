<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fiscal_period_id' => ['required', 'integer', 'exists:fiscal_periods,id'],
            'account_code' => ['required', 'string', 'max:20'],
            'budget_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
