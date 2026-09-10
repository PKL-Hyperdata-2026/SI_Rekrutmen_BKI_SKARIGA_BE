<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentPortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fileSize = null;
        $fileName = $this->original_filename ?? ($this->file_path ? basename($this->file_path) : null);
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            $bytes = Storage::disk('public')->size($this->file_path);
            if ($bytes >= 1048576) {
                $fileSize = number_format($bytes / 1048576, 1).' MB';
            } elseif ($bytes >= 1024) {
                $fileSize = number_format($bytes / 1024, 1).' KB';
            } else {
                $fileSize = $bytes.' B';
            }
        }

        return [
            'id' => $this->id,
            'studentAlumniId' => $this->student_alumni_id,
            'categoryId' => $this->category_id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'code' => $this->category->code,
                'name' => $this->category->name,
                'sortOrder' => $this->category->sort_order,
            ] : null,
            'title' => $this->title,
            'description' => $this->description,
            'fileName' => $fileName,
            'originalFilename' => $this->original_filename ?? $fileName,
            'fileSize' => $fileSize,
            'filePath' => $this->file_path,
            'fileUrl' => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
