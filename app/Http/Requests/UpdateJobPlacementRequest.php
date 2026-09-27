<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\JobApplication;
use App\Models\JobPlacement;
use App\Services\JobPlacementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobPlacementRequest extends FormRequest
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
                'sometimes',
                'required',
                'integer',
                Rule::exists('students_alumni', 'id')->whereNull('deleted_at'),
            ],
            'company_id' => ['sometimes', 'required', 'integer', 'exists:companies,id'],
            'job_application_id' => [
                'sometimes',
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
            'placement_status_id' => ['sometimes', 'nullable', 'integer', 'exists:standard_types,id'],
            'period' => ['sometimes', 'nullable', 'string', 'in:3,6,12'],
            'work_status' => ['sometimes', 'nullable', 'string'],
            'evaluations' => ['sometimes', 'nullable', 'array'],
            'accepted_date' => ['sometimes', 'nullable', 'date'],
            'start_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:accepted_date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $applicationId = $this->input('job_application_id');
            $studentId = $this->input('student_alumni_id')
                ?? $this->route('jobPlacement')?->student_alumni_id;
            $companyId = $this->input('company_id');

            if ($applicationId && $studentId && $companyId) {
                $owned = JobApplication::where('id', $applicationId)
                    ->where('student_alumni_id', $studentId)
                    ->whereHas('jobVacancy', fn (Builder $v) => $v->where('company_id', $companyId))
                    ->exists();

                if (! $owned) {
                    $validator->errors()->add('student_alumni_id', 'Data siswa atau alumni tidak terkait dengan lamaran pada perusahaan Anda.');
                }
            }

            $period = (string) $this->input('period');
            if (! in_array($period, ['6', '12'], true)) {
                return;
            }

            $placement = $this->route('jobPlacement');
            if (! $placement) {
                return;
            }

            $evaluations = is_array($placement->evaluations) ? $placement->evaluations : [];
            $has3 = ! empty($evaluations['3']['status']);
            $has6 = ! empty($evaluations['6']['status']);

            if ($period === '6' && ! $has3) {
                $validator->errors()->add('period', 'Evaluasi monitoring 3 bulan harus diisi terlebih dahulu sebelum 6 bulan.');
            }

            if ($period === '12' && ! $has3) {
                $validator->errors()->add('period', 'Evaluasi monitoring 3 bulan harus diisi terlebih dahulu.');
            } elseif ($period === '12' && ! $has6) {
                $validator->errors()->add('period', 'Evaluasi monitoring 6 bulan harus diisi terlebih dahulu sebelum 12 bulan.');
            }

            if ($period === '6' && $has3 && JobPlacement::isTerminalStatus($evaluations['3']['status'])) {
                $validator->errors()->add('period', 'Pekerja sudah resign/kontrak habis pada monitoring 3 bulan.');
            }

            if ($period === '12' && $has6 && JobPlacement::isTerminalStatus($evaluations['6']['status'])) {
                $validator->errors()->add('period', 'Pekerja sudah resign/kontrak habis pada monitoring 6 bulan.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'student_alumni_id.required' => 'Data siswa atau alumni wajib dipilih.',
            'student_alumni_id.exists' => 'Data siswa atau alumni tidak ditemukan.',
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
            'job_application_id.exists' => 'Lamaran pekerjaan yang dipilih tidak valid.',
            'placement_status_id.exists' => 'Status penempatan yang dipilih tidak valid.',
            'accepted_date.date' => 'Format tanggal diterima tidak valid.',
            'start_date.date' => 'Format tanggal mulai kerja tidak valid.',
            'start_date.after_or_equal' => 'Tanggal masuk kerja tidak boleh lebih awal dari tanggal diterima.',
        ];
    }
}
