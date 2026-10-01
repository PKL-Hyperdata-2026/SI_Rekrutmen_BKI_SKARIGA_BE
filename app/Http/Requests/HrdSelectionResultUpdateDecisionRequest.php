<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class HrdSelectionResultUpdateDecisionRequest extends BaseFormRequest
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
            'decision' => ['required', 'string', 'in:diterima,tidak_diterima,cadangan,pending'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan seleksi wajib diisi.',
            'decision.in' => 'Keputusan harus salah satu dari: diterima, tidak_diterima, cadangan, pending.',
        ];
    }
}
