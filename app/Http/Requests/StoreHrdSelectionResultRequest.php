<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHrdSelectionResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'decision.in' => 'Keputusan seleksi tidak valid.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
            'letter_file.file' => 'Berkas surat harus berupa file yang valid.',
            'letter_file.mimes' => 'Format berkas surat harus berupa PDF, JPG, JPEG, atau PNG.',
            'letter_file.max' => 'Ukuran berkas surat maksimal 10MB.',
        ];
    }
}
