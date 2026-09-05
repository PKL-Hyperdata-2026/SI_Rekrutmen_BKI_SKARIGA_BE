<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetStudentJobVacanciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'company_id' => 'nullable|exists:companies,id',
            'job_type_id' => 'nullable|exists:standard_types,id',
            'target_applicant_id' => 'nullable|exists:standard_types,id',
            'major_id' => 'nullable|exists:majors,id',
            'work_location' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }
}
