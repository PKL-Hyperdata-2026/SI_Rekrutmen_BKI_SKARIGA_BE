<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrdApplicantReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->studentAlumni;
        $user = $student?->user;
        $major = $student?->major;
        $class = $student?->class;
        $vacancy = $this->jobVacancy;
        $result = $this->selectionResult;

        $reviewStatus = 'perlu_review';
        $reviewLabel = 'Perlu Review';

        if ($result && $result->admin_selection_status === 'lolos') {
            $reviewStatus = 'lolos_berkas';
            $reviewLabel = 'Lolos Berkas';
        } elseif ($result && $result->admin_selection_status === 'tidak_lolos') {
            $reviewStatus = 'ditolak';
            $reviewLabel = 'Ditolak';
        }

        $documents = [];

        if ($student && $student->relationLoaded('portfolios')) {
            foreach ($student->portfolios as $doc) {
                $documents[] = [
                    'id' => encrypt($doc->id),
                    'title' => $doc->title,
                    'originalFilename' => $doc->original_filename ?? $doc->title,
                    'categoryName' => $doc->relationLoaded('category') ? $doc->category?->name : null,
                    'fileUrl' => $doc->file_path ? '/storage/'.ltrim((string) $doc->file_path, '/') : null,
                    'uploadedAt' => $doc->created_at?->toIso8601String(),
                ];
            }
        }

        return [
            'id' => encrypt($this->id),
            'appliedAt' => $this->applied_at?->toIso8601String(),
            'applicant' => $student ? [
                'id' => encrypt($student->id),
                'name' => $user?->full_name ?? '-',
                'nis' => $student->nis,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ] : null,
            'education' => $student ? [
                'majorName' => $major?->name,
                'majorCode' => $major?->code,
                'className' => $class?->name,
                'graduationYear' => $student->graduation_year,
            ] : null,
            'vacancy' => $vacancy ? [
                'id' => encrypt($vacancy->id),
                'position' => $vacancy->position,
                'title' => $vacancy->title,
                'deadline' => $vacancy->deadline?->toIso8601String(),
            ] : null,
            'documents' => $documents,
            'reviewStatus' => $reviewStatus,
            'reviewStatusLabel' => $reviewLabel,
            'selectionResult' => $result ? [
                'adminSelectionStatus' => $result->admin_selection_status,
                'notes' => $result->notes,
                'updatedAt' => $result->updated_at?->toIso8601String(),
            ] : null,
            'status' => $this->status ? [
                'code' => $this->status->code,
                'name' => $this->status->name,
            ] : null,
            'canScheduleTest' => $reviewStatus === 'lolos_berkas',
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
