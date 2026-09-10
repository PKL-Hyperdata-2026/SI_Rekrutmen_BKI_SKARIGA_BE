<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{value: mixed, label: string, extra?: array<string, mixed>}
 */
class SelectOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->resource;
        $value = is_array($item) ? ($item['value'] ?? null) : null;

        return [
            'value' => is_int($value) || (is_string($value) && is_numeric($value))
                ? encrypt((string) $value)
                : $value,
            'label' => (string) (is_array($item) ? ($item['label'] ?? '') : ''),
            'extra' => encrypt_recursive(is_array($item) ? ($item['extra'] ?? []) : []),
        ];
    }
}
