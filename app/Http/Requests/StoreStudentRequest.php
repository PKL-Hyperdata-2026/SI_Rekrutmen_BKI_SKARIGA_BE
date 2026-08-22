<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students_alumni', 'nis')->whereNull('deleted_at'),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', 'exists:standard_types,id'],
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'employment_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'graduation_year' => ['nullable', 'integer', 'between:1900,2100'],
            'social_media' => ['nullable'],
            'current_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'starting_salary' => ['nullable', 'numeric', 'min:0'],
            'waiting_time_months' => ['nullable', 'integer', 'min:0'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar pada sistem.',
            'full_name.required' => 'Nama lengkap siswa wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan.',
            'phone.required' => 'Nomor handphone wajib diisi.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'class_id.exists' => 'Kelas yang dipilih tidak valid.',
            'major_id.required' => 'Jurusan wajib dipilih.',
            'major_id.exists' => 'Jurusan yang dipilih tidak valid.',
            'employment_status_id.exists' => 'Status keterserapan tidak valid.',
            'graduation_year.between' => 'Tahun kelulusan tidak valid.',
            'current_company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
        ];
    }
}
