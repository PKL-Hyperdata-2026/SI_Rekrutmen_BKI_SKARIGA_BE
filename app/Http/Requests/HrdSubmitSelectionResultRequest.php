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
            'psychotest_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mcu_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'decision' => ['nullable', 'string', 'in:diterima,tidak_diterima,cadangan,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'letter_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'psychotest_score.numeric' => 'Nilai psikotes harus berupa angka.',
            'psychotest_score.min' => 'Nilai psikotes minimal 0.',
            'psychotest_score.max' => 'Nilai psikotes maksimal 100.',
            'interview_score.numeric' => 'Nilai wawancara harus berupa angka.',
            'interview_score.min' => 'Nilai wawancara minimal 0.',
            'interview_score.max' => 'Nilai wawancara maksimal 100.',
            'mcu_score.numeric' => 'Nilai MCU harus berupa angka.',
            'mcu_score.min' => 'Nilai MCU minimal 0.',
            'mcu_score.max' => 'Nilai MCU maksimal 100.',
            'final_score.numeric' => 'Nilai akhir harus berupa angka.',
            'final_score.min' => 'Nilai akhir minimal 0.',
            'final_score.max' => 'Nilai akhir maksimal 100.',
            'decision.in' => 'Keputusan harus salah satu dari: diterima, tidak_diterima, cadangan, pending.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
            'letter_file.file' => 'Berkas surat penempatan tidak valid.',
            'letter_file.mimes' => 'Berkas surat penempatan harus berformat pdf, jpg, jpeg, atau png.',
            'letter_file.max' => 'Ukuran berkas surat penempatan maksimal 10 MB.',
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
