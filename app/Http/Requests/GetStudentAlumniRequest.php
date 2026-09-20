<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetStudentAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'graduation_year' => ['nullable', 'integer'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'employment_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'current_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'sort_by' => ['nullable', 'string', 'in:id,nis,graduation_year,current_position,starting_salary,waiting_time_months,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
