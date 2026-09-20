<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHrdTestScheduleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'job_vacancy_id' => ['required', 'integer', 'exists:job_vacancies,id'],
            'minimum_score' => ['required', 'numeric', 'min:0', 'max:1000'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'send_notification' => ['nullable', 'boolean'],
            'application_ids' => ['nullable', 'array'],
            'application_ids.*' => ['integer', 'exists:job_applications,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama agenda wajib diisi.',
            'job_vacancy_id.required' => 'Lowongan kerja wajib dipilih.',
            'job_vacancy_id.exists' => 'Lowongan kerja tidak valid.',
            'minimum_score.required' => 'Nilai minimum diterima wajib diisi.',
            'minimum_score.numeric' => 'Nilai minimum harus berupa angka.',
            'scheduled_date.required' => 'Tanggal pelaksanaan wajib diisi.',
            'scheduled_date.date' => 'Format tanggal pelaksanaan tidak valid.',
            'scheduled_time.required' => 'Waktu mulai wajib diisi.',
            'location.required' => 'Lokasi atau tautan online wajib diisi.',
            'application_ids.array' => 'Daftar ID pelamar harus berupa array.',
            'application_ids.*.exists' => 'Data pelamar tidak ditemukan.',
        ];
    }
}
