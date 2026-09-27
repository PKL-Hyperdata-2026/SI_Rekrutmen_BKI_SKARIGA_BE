<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\JobApplication;
use App\Services\JobPlacementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $companyId = app(JobPlacementService::class)->getCompanyIdByUserId($this->user()?->id);
        if ($companyId) {
            $this->merge(['company_id' => $companyId]);
        }
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id');

        return [
            'student_alumni_id' => [
                'required',
                'integer',
                Rule::exists('students_alumni', 'id')->whereNull('deleted_at'),
            ],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'job_application_id' => [
                'nullable',
                'integer',
                Rule::exists('job_applications', 'id')
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($companyId): void {
                        $q->whereIn('job_vacancy_id', function ($sub) use ($companyId): void {
                            $sub->select('id')->from('job_vacancies')
                                ->where('company_id', $companyId)
                                ->whereNull('deleted_at');
                        });
                    }),
            ],
            'placement_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'accepted_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date', 'after_or_equal:accepted_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $applicationId = $this->input('job_application_id');
            $studentId = $this->input('student_alumni_id');
            $companyId = $this->input('company_id');

            if (! $applicationId || ! $studentId || ! $companyId) {
                return;
            }

            $owned = JobApplication::where('id', $applicationId)
                ->where('student_alumni_id', $studentId)
                ->whereHas('jobVacancy', fn (Builder $v) => $v->where('company_id', $companyId))
                ->exists();

            if (! $owned) {
                $validator->errors()->add('student_alumni_id', 'Data siswa atau alumni tidak terkait dengan lamaran pada perusahaan Anda.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'student_alumni_id.required' => 'Data siswa atau alumni wajib dipilih.',
            'student_alumni_id.exists' => 'Data siswa atau alumni tidak ditemukan.',
            'company_id.required' => 'Akun HRD belum terhubung dengan data perusahaan.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
            'job_application_id.exists' => 'Lamaran pekerjaan yang dipilih tidak valid.',
            'placement_status_id.exists' => 'Status penempatan yang dipilih tidak valid.',
            'accepted_date.date' => 'Format tanggal diterima tidak valid.',
            'start_date.date' => 'Format tanggal mulai kerja tidak valid.',
            'start_date.after_or_equal' => 'Tanggal masuk kerja tidak boleh lebih awal dari tanggal diterima.',
        ];
    }
}
