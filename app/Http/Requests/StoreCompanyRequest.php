<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('companies', 'name')->whereNull('deleted_at'),
            ],
            'industry_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'website' => ['nullable', 'url', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_contact' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
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
