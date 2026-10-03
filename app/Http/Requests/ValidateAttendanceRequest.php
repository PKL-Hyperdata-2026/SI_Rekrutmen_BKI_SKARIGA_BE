<?php

declare(strict_types=1);

namespace App\Http\Requests;

class ValidateAttendanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'validation_status' => ['required', 'string', 'in:verified,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'system_action' => ['nullable', 'string', 'max:255'],
        ];
    }
}
