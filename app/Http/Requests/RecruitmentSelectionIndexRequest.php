<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class RecruitmentSelectionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_vacancy_id' => ['nullable', 'integer', 'exists:job_vacancies,id'],
            'stage_id' => ['nullable', 'integer', 'exists:selection_stages,id'],
            'attendance_status' => ['nullable', 'string', 'in:hadir,tidak_hadir,belum,hadir_tidak_hadir,present,absent,leave'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'job_vacancy_id.exists' => 'Lowongan kerja tidak ditemukan.',
            'stage_id.exists' => 'Tahapan seleksi tidak ditemukan.',
            'attendance_status.in' => 'Status kehadiran harus salah satu dari: hadir, tidak_hadir, belum.',
            'search.max' => 'Kata kunci pencarian maksimal 100 karakter.',
            'per_page.integer' => 'Parameter per_page tidak valid.',
            'per_page.min' => 'Jumlah data per halaman minimal 1.',
            'per_page.max' => 'Jumlah data per halaman maksimal 100.',
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
