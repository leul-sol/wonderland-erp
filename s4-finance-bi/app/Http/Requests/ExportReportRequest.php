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
            'report' => ['required', 'string', 'max:60'],
            'format' => ['required', 'in:csv,pdf,excel'],
            'fiscal_period_id' => ['nullable', 'integer', 'exists:fiscal_periods,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'payroll_run_id' => ['nullable', 'integer', 'min:1'],
            'guarantor_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
