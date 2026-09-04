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
        return [
            'phone' => ['required', 'string', 'max:25'],
            'employment_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'social_media' => ['sometimes', 'nullable', 'array', 'max:20'],
            'social_media.*.platform' => ['required', 'string', Rule::in(array_keys(config('social_media_platforms')))],
            'social_media.*.username' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Nomor WhatsApp aktif wajib diisi.',
            'employment_status_id.exists' => 'Status keterserapan kerja yang dipilih tidak valid.',
            'social_media.max' => 'Maksimal 20 akun sosial media.',
            'social_media.*.platform.required' => 'Platform sosial media wajib dipilih.',
            'social_media.*.platform.in' => 'Platform sosial media yang dipilih tidak valid.',
            'social_media.*.username.required' => 'Username sosial media wajib diisi.',
            'social_media.*.username.max' => 'Username sosial media maksimal 255 karakter.',
        ];
    }
}
