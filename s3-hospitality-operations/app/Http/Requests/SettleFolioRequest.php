<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettleFolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,bank,pos,visa'],
        ];
    }
}
