<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetHrdJobVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'target_applicant_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'job_type_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => ['integer', 'exists:majors,id'],
            'majors' => ['nullable', 'array'],
            'majors.*' => ['integer', 'exists:majors,id'],
            'is_active' => ['nullable', 'boolean'],
            'effective_status' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
