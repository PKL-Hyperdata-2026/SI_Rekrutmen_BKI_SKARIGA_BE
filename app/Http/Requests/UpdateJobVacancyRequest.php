<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id'          => 'sometimes|required|exists:companies,id',
            'job_type_id'         => 'nullable|exists:standard_types,id',
            'status_id'           => 'nullable|exists:standard_types,id',
            'target_applicant_id' => 'nullable|exists:standard_types,id',
            'title'               => 'sometimes|required|string|max:255',
            'position'            => 'nullable|string|max:255',
            'description'         => 'nullable|string',
            'qualification'       => 'nullable|string',
            'quota'               => 'nullable|integer|min:1',
            'deadline'            => 'nullable|date',
            'work_location'       => 'nullable|string|max:255',
            'min_salary'          => 'nullable|numeric|min:0|max:9999999999999.99',
            'max_salary'          => 'nullable|numeric|gte:min_salary|max:9999999999999.99',
            'is_featured'         => 'boolean',
            'is_active'           => 'boolean',
            'major_ids'           => 'nullable|array',
            'major_ids.*'         => 'exists:majors,id',
        ];
    }
}
