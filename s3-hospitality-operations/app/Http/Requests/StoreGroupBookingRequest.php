<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.guest_name' => ['required', 'string', 'max:150'],
            'rooms.*.guest_email' => ['nullable', 'email', 'max:150'],
            'rooms.*.guest_phone' => ['nullable', 'string', 'max:30'],
            'rooms.*.room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'rooms.*.adults' => ['nullable', 'integer', 'min:1', 'max:10'],
            'rooms.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
