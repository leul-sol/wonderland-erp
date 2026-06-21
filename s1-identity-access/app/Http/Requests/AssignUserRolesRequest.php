<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*.role_id' => ['required', 'integer', 'exists:roles,id'],
            'roles.*.department_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
