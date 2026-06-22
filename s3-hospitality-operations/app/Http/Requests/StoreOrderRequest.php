<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folio_id' => ['nullable', 'integer', 'exists:folios,id'],
            'employee_consumption_period_id' => ['nullable', 'integer', 'exists:employee_consumption_periods,id'],
        ];
    }
}
