<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_type_id' => ['required', 'integer', 'exists:asset_types,id'],
            'serial_number' => ['nullable', 'string', 'max:80'],
            'assigned_date' => ['nullable', 'date'],
        ];
    }
}
