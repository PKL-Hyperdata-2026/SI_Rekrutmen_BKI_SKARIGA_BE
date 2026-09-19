<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class HrdApplicantReviewIndexRequest extends FormRequest
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
            'job_vacancy_id' => ['nullable', 'integer', 'exists:job_vacancies,id'],
            'review_status' => ['nullable', 'string', 'in:semua,perlu_review,lolos_berkas,ditolak'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', 'in:applied_at,name,position'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'job_vacancy_id.integer' => 'ID lowongan kerja tidak valid.',
            'job_vacancy_id.exists' => 'Lowongan kerja tidak ditemukan.',
            'review_status.in' => 'Status review harus salah satu dari: semua, perlu_review, lolos_berkas, ditolak.',
            'search.max' => 'Kata kunci pencarian maksimal 100 karakter.',
            'per_page.integer' => 'Parameter per_page tidak valid.',
            'per_page.min' => 'Jumlah data per halaman minimal 1.',
            'per_page.max' => 'Jumlah data per halaman maksimal 100.',
            'page.integer' => 'Parameter page tidak valid.',
            'sort_by.in' => 'Kolom urutan tidak valid.',
            'sort_dir.in' => 'Arah urutan harus asc atau desc.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'data' => null,
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
