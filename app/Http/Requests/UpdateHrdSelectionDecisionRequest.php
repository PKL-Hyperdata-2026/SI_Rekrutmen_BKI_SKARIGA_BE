<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHrdSelectionDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:diterima,tidak_diterima,cadangan,pending'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan seleksi wajib dipilih.',
            'decision.in' => 'Keputusan seleksi tidak valid.',
        ];
    }
}
