<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\AccessMenuService;
use Illuminate\Validation\Rule;

class UpdateAccessMenuRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(AccessMenuService::ROLES)],
            'menu_ids' => ['present', 'array'],
            'menu_ids.*' => ['integer', 'exists:menus,id'],
        ];
    }
}
