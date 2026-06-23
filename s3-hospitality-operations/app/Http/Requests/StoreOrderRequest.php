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
            'customer_type' => ['nullable', 'string', 'in:hotel_guest,employee,family_member,management,event,outside_cash,outside_credit'],
            'customer_ref_id' => ['nullable', 'integer'],
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
        ];
    }
}
