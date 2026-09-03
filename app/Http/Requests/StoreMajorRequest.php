<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('majors', 'code')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'Departemen wajib dipilih.',
            'department_id.exists' => 'Departemen yang dipilih tidak valid.',
            'code.required' => 'Kode jurusan wajib diisi.',
            'code.unique' => 'Kode jurusan sudah digunakan.',
            'name.required' => 'Nama jurusan wajib diisi.',
        ];
    }
}
