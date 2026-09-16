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
            'id' => encrypt($this->id),
            'userId' => $this->user_id ? encrypt($this->user_id) : null,
            'industryId' => $this->industry_id ? encrypt($this->industry_id) : null,
            'industry' => $this->whenLoaded('industry', function () {
                return $this->industry ? [
                    'id' => encrypt($this->industry->id),
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
                    'id' => encrypt($this->createdBy->id),
                    'fullName' => $this->createdBy->full_name,
                    'email' => $this->createdBy->email,
                ] : null;
            }),
            'updatedByUser' => $this->whenLoaded('updatedBy', function () {
                return $this->updatedBy ? [
                    'id' => encrypt($this->updatedBy->id),
                    'fullName' => $this->updatedBy->full_name,
                    'email' => $this->updatedBy->email,
                ] : null;
            }),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
