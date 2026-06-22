<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInGroupBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'assignments.*.room_id' => ['required', 'integer', 'exists:rooms,id'],
        ];
    }
}
