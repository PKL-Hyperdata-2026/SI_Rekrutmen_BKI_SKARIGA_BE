<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => encrypt($this->id),
            'fullName' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'company' => $this->whenLoaded('company', function () {
                return $this->company ? [
                    'id' => encrypt($this->company->id),
                    'name' => $this->company->name,
                    'address' => $this->company->address,
                    'email' => $this->company->email,
                    'phone' => $this->company->phone,
                ] : null;
            }),
        ];
    }
}
