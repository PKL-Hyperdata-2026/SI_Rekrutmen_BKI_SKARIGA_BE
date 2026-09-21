<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class HrdBulkSubmitSelectionResultRequest extends FormRequest
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
            'results' => ['required', 'array', 'min:1', 'max:100'],
            'results.*.stage_history_id' => ['nullable', 'integer', 'exists:application_stage_histories,id'],
            'results.*.application_id' => ['nullable', 'integer', 'exists:job_applications,id'],
            'results.*.selection_stage_id' => ['nullable', 'integer', 'exists:selection_stages,id'],
            'results.*.decision' => ['required', 'string', 'in:passed,failed,accepted,absent'],
            'results.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'results.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'results.required' => 'Daftar hasil evaluasi wajib diisi.',
            'results.array' => 'Format daftar hasil evaluasi tidak valid.',
            'results.min' => 'Daftar hasil evaluasi minimal 1 data.',
            'results.max' => 'Daftar hasil evaluasi maksimal 100 data.',
            'results.*.decision.required' => 'Keputusan evaluasi setiap peserta wajib dipilih.',
            'results.*.decision.in' => 'Keputusan harus salah satu dari: passed, failed, accepted, absent.',
            'results.*.score.numeric' => 'Nilai tes harus berupa angka.',
            'results.*.score.min' => 'Nilai tes minimal 0.',
            'results.*.score.max' => 'Nilai tes maksimal 100.',
            'results.*.notes.max' => 'Catatan maksimal 1000 karakter.',
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
