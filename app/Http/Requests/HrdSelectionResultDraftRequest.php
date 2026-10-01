<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class HrdSelectionResultDraftRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'hrd';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_vacancy_id' => ['required', 'integer', 'exists:job_vacancies,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'job_vacancy_id.required' => 'Lowongan kerja wajib dipilih.',
            'job_vacancy_id.integer' => 'ID lowongan kerja tidak valid.',
            'job_vacancy_id.exists' => 'Lowongan kerja tidak ditemukan.',
        ];
    }
}
