<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHrdJobVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'position' => 'nullable|string|max:255',
            'title' => 'required_without:position|nullable|string|max:255',
            'quota' => 'nullable|integer|min:1',
            'deadline' => 'nullable|date',
            'major_ids' => 'nullable|array',
            'major_ids.*' => 'exists:majors,id',
            'target_applicant_id' => 'nullable|exists:standard_types,id',
            'work_location' => 'nullable|string|max:255',
            'qualification' => 'nullable|string',
            'description' => 'nullable|string',
            'job_type_id' => 'nullable|exists:standard_types,id',
            'status_id' => 'nullable|exists:standard_types,id',
            'min_salary' => 'nullable|numeric|min:0|max:9999999999999.99',
            'max_salary' => 'nullable|numeric|gte:min_salary|max:9999999999999.99',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'send_notification' => 'nullable|boolean',
        ];
    }
}
