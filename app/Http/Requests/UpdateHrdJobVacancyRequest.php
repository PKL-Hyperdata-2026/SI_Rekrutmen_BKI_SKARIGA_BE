<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\JobVacancy;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHrdJobVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'position' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:255',
            'quota' => 'sometimes|required|integer|min:1',
            'deadline' => [
                'sometimes',
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $vacancy = $this->route('jobVacancy');
                    if (! ($vacancy instanceof JobVacancy) && (is_numeric($vacancy) || is_string($vacancy))) {
                        $vacancy = JobVacancy::find($vacancy);
                    }
                    if ($vacancy instanceof JobVacancy) {
                        $originalDeadline = $vacancy->deadline?->format('Y-m-d');
                        if ($value === $originalDeadline) {
                            return;
                        }
                    }
                    if (is_string($value) && strtotime($value) < strtotime(today()->toDateString())) {
                        $fail('Batas pendaftaran tidak boleh di masa lalu.');
                    }
                },
            ],
            'major_ids' => 'sometimes|required|array|min:1',
            'major_ids.*' => [
                Rule::exists('majors', 'id')->where('is_active', true),
            ],
            'target_applicant_id' => 'sometimes|required|exists:standard_types,id',
            'work_location' => 'sometimes|required|string|max:255',
            'qualification' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'job_type_id' => 'nullable|exists:standard_types,id',
            'status_id' => 'nullable|exists:standard_types,id',
            'min_salary' => 'nullable|numeric|min:0|max:9999999999999.99',
            'max_salary' => 'nullable|numeric|gte:min_salary|max:9999999999999.99',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'send_notification' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'position.required' => 'Posisi pekerjaan wajib diisi.',
            'quota.required' => 'Kuota wajib diisi.',
            'quota.integer' => 'Kuota harus berupa angka bulat.',
            'quota.min' => 'Kuota minimal 1 orang.',
            'deadline.required' => 'Batas pendaftaran wajib diisi.',
            'deadline.date' => 'Batas pendaftaran harus berupa tanggal yang valid.',
            'deadline.after_or_equal' => 'Batas pendaftaran tidak boleh di masa lalu.',
            'major_ids.required' => 'Minimal satu kategori jurusan harus dipilih.',
            'major_ids.array' => 'Kategori jurusan harus berupa array.',
            'major_ids.min' => 'Minimal satu kategori jurusan harus dipilih.',
            'major_ids.*.exists' => 'Kategori jurusan yang dipilih tidak valid atau tidak aktif.',
            'target_applicant_id.required' => 'Target pelamar wajib dipilih.',
            'target_applicant_id.exists' => 'Target pelamar yang dipilih tidak valid.',
            'work_location.required' => 'Lokasi kerja wajib diisi.',
            'qualification.required' => 'Kualifikasi/persyaratan wajib diisi.',
        ];
    }
}
