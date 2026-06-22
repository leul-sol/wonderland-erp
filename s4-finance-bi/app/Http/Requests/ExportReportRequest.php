<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report' => ['required', 'in:trial_balance,income_statement,balance_sheet,cash_flow,revenue_by_source'],
            'format' => ['required', 'in:csv'],
            'fiscal_period_id' => ['nullable', 'integer', 'exists:fiscal_periods,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }
}
