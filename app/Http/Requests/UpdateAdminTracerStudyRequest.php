<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminTracerStudyRequest extends FormRequest
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
            'career_status' => [
                'required',
                'string',
                Rule::in(['bekerja', 'wirausaha', 'lanjut_studi', 'mencari_pekerjaan']),
            ],

            // Validasi Khusus Status: Bekerja
            'company_name'   => ['required_if:career_status,bekerja', 'nullable', 'string', 'max:255'],
            'company_sector' => ['nullable', 'string', 'max:255'],
            'job_title'      => ['required_if:career_status,bekerja', 'nullable', 'string', 'max:255'],
            'job_location'   => ['nullable', 'string', 'max:255'],
            'minimum_salary' => ['nullable', 'numeric', 'min:0'],
            'maximum_salary' => ['nullable', 'numeric', 'gte:minimum_salary'],
            'waiting_period' => ['required_if:career_status,bekerja', 'nullable', 'string', 'max:255'],
            'accepted_date'  => ['nullable', 'date'],
            'start_date'     => ['required_if:career_status,bekerja', 'nullable', 'date'],

            // Validasi Khusus Status: Wirausaha
            'business_name'       => ['required_if:career_status,wirausaha', 'nullable', 'string', 'max:255'],
            'business_address'    => ['required_if:career_status,wirausaha', 'nullable', 'string'],
            'instagram_handle'    => ['nullable', 'string', 'max:255'],
            'average_income'      => ['nullable', 'string', 'max:255'],
            'business_field'      => ['required_if:career_status,wirausaha', 'nullable', 'string', 'max:255'],
            'business_start_date' => ['nullable', 'date'],

            // Validasi Khusus Status: Lanjut Studi
            'university_name' => ['required_if:career_status,lanjut_studi', 'nullable', 'string', 'max:255'],
            'study_program'   => ['required_if:career_status,lanjut_studi', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'career_status.required' => 'Status karir wajib dipilih.',
            'career_status.in'       => 'Pilihan status karir tidak valid.',

            // Bekerja
            'company_name.required_if'   => 'Nama perusahaan/tempat kerja wajib diisi.',
            'job_title.required_if'      => 'Posisi/jabatan wajib diisi.',
            'waiting_period.required_if' => 'Masa tunggu kerja wajib dipilih.',
            'start_date.required_if'     => 'Tanggal masuk kerja wajib diisi.',
            'maximum_salary.gte'         => 'Gaji maksimum harus lebih besar atau sama dengan gaji minimum.',

            // Wirausaha
            'business_name.required_if'    => 'Nama wirausaha wajib diisi.',
            'business_address.required_if' => 'Alamat wirausaha wajib diisi.',
            'business_field.required_if'   => 'Bidang usaha wajib dipilih.',

            // Lanjut Studi
            'university_name.required_if' => 'Nama universitas/kampus wajib diisi.',
            'study_program.required_if'   => 'Program studi wajib diisi.',
        ];
    }
}
