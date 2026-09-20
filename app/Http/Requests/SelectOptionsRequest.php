<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectOptionsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'for_select' => ['nullable'],
            'eligible' => ['nullable'],
            'industry_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'major_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'employment_status_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.max' => 'Kata kunci pencarian maksimal 100 karakter.',
            'page.integer' => 'Parameter halaman tidak valid.',
            'page.min' => 'Parameter halaman tidak valid.',
            'per_page.integer' => 'Parameter jumlah data per halaman tidak valid.',
            'per_page.min' => 'Jumlah data per halaman minimal 1.',
            'per_page.max' => 'Jumlah data per halaman maksimal 100.',
        ];
    }
}
