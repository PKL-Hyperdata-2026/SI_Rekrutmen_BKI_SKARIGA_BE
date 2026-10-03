<?php

declare(strict_types=1);

namespace App\Http\Requests;

class BulkValidateAttendanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_ids' => ['required', 'array', 'min:1'],
            'attendance_ids.*' => ['required', 'integer'],
            'validation_status' => ['required', 'string', 'in:verified,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'system_action' => ['nullable', 'string', 'max:255'],
        ];
    }
}
