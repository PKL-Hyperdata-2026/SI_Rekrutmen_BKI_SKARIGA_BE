<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('companies', 'name')
                    ->ignore($company?->id)
                    ->whereNull('deleted_at'),
            ],
            'industry_id' => ['sometimes', 'nullable', 'integer', 'exists:standard_types,id'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('companies', 'email')
                    ->ignore($company?->id)
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['sometimes', 'nullable', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'pic_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pic_contact' => ['sometimes', 'nullable', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'name.min' => 'Nama perusahaan minimal 3 karakter.',
            'name.unique' => 'Nama perusahaan sudah terdaftar.',
            'industry_id.exists' => 'Industri yang dipilih tidak valid.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan perusahaan lain.',
            'phone.regex' => 'Format nomor handphone tidak valid (contoh: 08xxxxxxxxxx).',
            'website.url' => 'Format alamat website tidak valid.',
            'pic_contact.regex' => 'Format kontak PIC tidak valid (contoh: 08xxxxxxxxxx).',
        ];
    }
}
