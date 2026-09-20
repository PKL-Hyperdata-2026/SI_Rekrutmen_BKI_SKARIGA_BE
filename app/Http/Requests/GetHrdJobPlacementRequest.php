<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetHrdJobPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'student_alumni_id' => ['nullable', 'integer', 'exists:students_alumni,id'],
            'placement_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'job_application_id' => ['nullable', 'integer', 'exists:job_applications,id'],
            'year' => ['nullable', 'integer'],
            'sort_by' => ['nullable', 'string', 'in:id,accepted_date,start_date,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
