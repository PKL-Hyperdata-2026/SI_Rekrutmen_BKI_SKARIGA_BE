<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $student = $user?->studentAlumni;
        $studentId = $student?->id;
        $userId = $user?->id;
        $isSiswa = $user?->role === 'siswa';

        return [
            'nis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students_alumni', 'nis')
                    ->ignore($studentId)
                    ->whereNull('deleted_at'),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['required', 'string', 'max:25'],
            'class_id' => array_merge(
                $isSiswa ? ['required', 'integer'] : ['nullable', 'integer'],
                ['exists:standard_types,id']
            ),
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'employment_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'graduation_year' => ['nullable', 'integer', 'between:1900,2100'],
            'social_media' => ['sometimes', 'nullable', 'array', 'max:20'],
            'social_media.*.platform' => ['required', 'string', Rule::in(array_keys(config('social_media_platforms')))],
            'social_media.*.username' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.required' => 'NIS/NISN wajib diisi.',
            'nis.unique' => 'NIS/NISN sudah terdaftar pada akun lain.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan oleh akun lain.',
            'phone.required' => 'Nomor WhatsApp aktif wajib diisi.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'class_id.integer' => 'Kelas yang dipilih tidak valid.',
            'class_id.exists' => 'Kelas yang dipilih tidak valid.',
            'major_id.required' => 'Jurusan wajib dipilih.',
            'major_id.exists' => 'Jurusan yang dipilih tidak valid.',
            'employment_status_id.exists' => 'Status keterserapan kerja yang dipilih tidak valid.',
            'graduation_year.between' => 'Tahun kelulusan tidak valid.',
            'social_media.max' => 'Maksimal 20 akun sosial media.',
            'social_media.*.platform.required' => 'Platform sosial media wajib dipilih.',
            'social_media.*.platform.in' => 'Platform sosial media yang dipilih tidak valid.',
            'social_media.*.username.required' => 'Username sosial media wajib diisi.',
            'social_media.*.username.max' => 'Username sosial media maksimal 255 karakter.',
        ];
    }
}
