<?php

declare(strict_types=1);

namespace App\Http\Requests;

class ApplyJobVacancyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
