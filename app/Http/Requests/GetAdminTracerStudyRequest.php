<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAdminTracerStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'career_status' => ['nullable', 'string', 'max:50'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'graduation_year' => ['nullable', 'integer'],
            'sort_by' => ['nullable', 'string', 'in:id,career_status,minimum_salary,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
