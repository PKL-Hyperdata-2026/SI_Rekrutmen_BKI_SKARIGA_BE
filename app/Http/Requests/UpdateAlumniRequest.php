<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $alumni = $this->route('alumni');

        return [
            'nis' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('students_alumni', 'nis')
                    ->ignore($alumni?->id)
                    ->whereNull('deleted_at'),
            ],
            'user_id' => ['sometimes', 'nullable', 'integer'],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($alumni?->user_id)
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'major_id' => ['sometimes', 'required', 'integer', 'exists:majors,id'],
            'class_id' => ['sometimes', 'nullable', 'integer', 'exists:standard_types,id'],
            'graduation_year' => ['sometimes', 'required', 'integer', 'between:1900,2100'],
            'employment_status_id' => ['sometimes', 'nullable', 'integer', 'exists:standard_types,id'],
            'current_company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'current_position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'starting_salary' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'waiting_time_months' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'social_media' => ['sometimes', 'nullable', 'array'],
            'social_media.profile_url' => ['sometimes', 'nullable', 'url'],
            'social_media.linkedin' => ['sometimes', 'nullable', 'url'],
            'social_media.github' => ['sometimes', 'nullable', 'url'],
            'social_media.instagram' => ['sometimes', 'nullable', 'url'],
            'social_media.tiktok' => ['sometimes', 'nullable', 'url'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'major_id.required' => 'Jurusan wajib diisi.',
            'major_id.exists' => 'Jurusan yang dipilih tidak valid.',
            'graduation_year.between' => 'Tahun lulus tidak valid.',
        ];
    }
}
