<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHrdTestScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'hrd';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'stage_type_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'minimum_score' => ['sometimes', 'required', 'numeric', 'min:0', 'max:1000'],
            'scheduled_date' => ['sometimes', 'required', 'date'],
            'scheduled_time' => ['sometimes', 'required', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'send_notification' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama agenda wajib diisi.',
            'stage_type_id.exists' => 'Tipe tahapan seleksi tidak valid.',
            'minimum_score.required' => 'Nilai minimum diterima wajib diisi.',
            'minimum_score.numeric' => 'Nilai minimum harus berupa angka.',
            'scheduled_date.required' => 'Tanggal pelaksanaan wajib diisi.',
            'scheduled_date.date' => 'Format tanggal pelaksanaan tidak valid.',
            'scheduled_time.required' => 'Waktu mulai wajib diisi.',
            'scheduled_time.regex' => 'Format waktu mulai harus berupa jam:menit (contoh: 08:00).',
            'location.required' => 'Lokasi atau tautan online wajib diisi.',
        ];
    }
}
