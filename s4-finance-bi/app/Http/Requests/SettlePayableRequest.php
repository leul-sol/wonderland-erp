<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettlePayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank'],
        ];
    }
}
