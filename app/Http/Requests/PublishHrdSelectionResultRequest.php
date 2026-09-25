<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishHrdSelectionResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_vacancy_id' => ['required', 'integer', 'exists:job_vacancies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'job_vacancy_id.required' => 'Lowongan kerja wajib dipilih.',
            'job_vacancy_id.integer' => 'ID lowongan kerja harus berupa angka.',
            'job_vacancy_id.exists' => 'Lowongan kerja tidak ditemukan.',
        ];
    }
}
