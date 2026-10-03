<?php

declare(strict_types=1);

namespace App\Http\Requests;

class GetAttendanceStageSummariesRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_vacancy_id' => ['nullable', 'integer', 'exists:job_vacancies,id'],
        ];
    }
}
