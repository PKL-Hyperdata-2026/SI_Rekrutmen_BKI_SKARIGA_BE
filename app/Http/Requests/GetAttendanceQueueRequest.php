<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class GetAttendanceQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_vacancy_id' => ['nullable', 'integer', 'exists:job_vacancies,id'],
            'stage_id' => ['nullable', 'integer', 'exists:selection_stages,id'],
            'stage_ids' => ['nullable', 'array', 'max:50'],
            'stage_ids.*' => ['integer', 'exists:selection_stages,id'],
            'validation_status' => ['nullable', 'string', 'in:pending,verified,rejected'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'data' => null,
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
