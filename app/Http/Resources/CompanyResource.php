<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'industryId' => $this->industry_id,
            'industry' => $this->whenLoaded('industry', function () {
                return $this->industry ? [
                    'id' => $this->industry->id,
                    'code' => $this->industry->code,
                    'name' => $this->industry->name,
                    'metadata' => $this->industry->metadata,
                ] : null;
            }),
            'name' => $this->name,
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'picName' => $this->pic_name,
            'picContact' => $this->pic_contact,
            'logoPath' => $this->logo_path,
            'isActive' => (bool) $this->is_active,
            'createdByUser' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? [
                    'id' => $this->createdBy->id,
                    'fullName' => $this->createdBy->full_name,
                    'email' => $this->createdBy->email,
                ] : null;
            }),
            'updatedByUser' => $this->whenLoaded('updatedBy', function () {
                return $this->updatedBy ? [
                    'id' => $this->updatedBy->id,
                    'fullName' => $this->updatedBy->full_name,
                    'email' => $this->updatedBy->email,
                ] : null;
            }),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
