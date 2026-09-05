<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'password'   => ['required', 'string', 'min:6'],
            'role'       => ['required', 'string', Rule::in(['admin', 'hrd', 'siswa', 'alumni'])],
            'is_active'  => ['nullable', 'boolean'],
            'company_id' => [
                Rule::requiredIf(fn () => $this->input('role') === 'hrd'),
                'nullable',
                'integer',
                'exists:companies,id',
            ],
        ];
    }
}
