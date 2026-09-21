<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class HrdSubmitSelectionResultRequest extends FormRequest
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
            'stage_history_id' => ['nullable', 'integer', 'exists:application_stage_histories,id'],
            'application_id' => ['nullable', 'integer', 'exists:job_applications,id'],
            'selection_stage_id' => ['nullable', 'integer', 'exists:selection_stages,id'],
            'decision' => ['required', 'string', 'in:passed,failed,accepted,absent'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stage_history_id.integer' => 'ID tahapan seleksi tidak valid.',
            'stage_history_id.exists' => 'Data tahapan seleksi tidak ditemukan.',
            'application_id.integer' => 'ID lamaran tidak valid.',
            'application_id.exists' => 'Data lamaran tidak ditemukan.',
            'selection_stage_id.integer' => 'ID sesi tes tidak valid.',
            'selection_stage_id.exists' => 'Sesi tes tidak ditemukan.',
            'decision.required' => 'Keputusan evaluasi wajib dipilih.',
            'decision.in' => 'Keputusan harus salah satu dari: passed, failed, accepted, absent.',
            'score.numeric' => 'Nilai tes harus berupa angka.',
            'score.min' => 'Nilai tes minimal 0.',
            'score.max' => 'Nilai tes maksimal 100.',
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
