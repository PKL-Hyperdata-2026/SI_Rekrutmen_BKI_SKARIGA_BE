<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentPortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:standard_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Jenis portofolio / dokumen wajib dipilih.',
            'category_id.exists' => 'Jenis portofolio / dokumen tidak valid.',
            'title.required' => 'Judul dokumen wajib diisi.',
            'file.required' => 'File dokumen wajib diunggah.',
            'file.file' => 'File yang diunggah harus berupa file yang valid.',
            'file.mimes' => 'Format file yang diperbolehkan: PDF, JPG, PNG, DOC, DOCX.',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ];
    }
}
