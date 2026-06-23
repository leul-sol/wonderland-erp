<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnEmployeeAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'returned_date' => ['nullable', 'date'],
            'condition_on_return' => ['nullable', 'string', 'max:255'],
        ];
    }
}
