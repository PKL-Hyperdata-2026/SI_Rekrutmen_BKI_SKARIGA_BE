<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class HrdApplicantBulkReviewRequest extends FormRequest
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
            'application_ids' => ['required', 'array', 'min:1', 'max:100'],
            'application_ids.*' => ['integer', 'exists:job_applications,id'],
            'decision' => ['required', 'string', 'in:lolos,tidak_lolos'],
            'notes' => ['required_if:decision,tidak_lolos', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'application_ids.required' => 'Daftar pelamar wajib diisi.',
            'application_ids.array' => 'Daftar pelamar harus berupa array.',
            'application_ids.min' => 'Pilih minimal 1 pelamar.',
            'application_ids.max' => 'Maksimal 100 pelamar per aksi massal.',
            'application_ids.*.integer' => 'ID lamaran tidak valid.',
            'application_ids.*.exists' => 'Data lamaran tidak ditemukan.',
            'decision.required' => 'Keputusan review wajib diisi.',
            'decision.in' => 'Keputusan harus salah satu dari: lolos, tidak_lolos.',
            'notes.required_if' => 'Alasan penolakan wajib diisi.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
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
