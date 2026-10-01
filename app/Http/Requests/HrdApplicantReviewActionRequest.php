<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class HrdApplicantReviewActionRequest extends BaseFormRequest
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
            'decision.required' => 'Keputusan review wajib diisi.',
            'decision.in' => 'Keputusan harus salah satu dari: lolos, tidak_lolos.',
            'notes.required_if' => 'Alasan penolakan wajib diisi.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
