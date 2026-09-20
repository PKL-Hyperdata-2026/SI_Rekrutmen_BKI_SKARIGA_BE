<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'applicant_type' => ['nullable', 'string', 'in:siswa,alumni'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'graduation_year' => ['nullable', 'integer'],
        ];
    }
}
