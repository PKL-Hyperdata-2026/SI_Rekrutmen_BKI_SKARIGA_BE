<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'siswa'),
            ],
            'nis' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('students_alumni', 'nis')
                    ->whereNull('deleted_at')
                    ->where('user_id', '!=', $this->input('user_id')),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'class_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'graduation_year' => ['required', 'integer', 'between:1900,2100'],
            'employment_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'current_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'starting_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'waiting_time_months' => ['nullable', 'integer', 'min:0'],
            'social_media' => ['nullable', 'array'],
            'social_media.linkedin' => ['nullable', 'url'],
            'social_media.github' => ['nullable', 'url'],
            'social_media.instagram' => ['nullable', 'url'],
            'social_media.tiktok' => ['nullable', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Akun siswa wajib dipilih.',
            'user_id.exists' => 'Akun siswa yang dipilih tidak valid.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'major_id.required' => 'Jurusan wajib diisi.',
            'major_id.exists' => 'Jurusan yang dipilih tidak valid.',
            'graduation_year.required' => 'Tahun lulus wajib diisi.',
            'graduation_year.between' => 'Tahun lulus tidak valid.',
        ];
    }
}
