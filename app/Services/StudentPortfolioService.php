<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\StudentPortfolio;
use App\Models\User;
use App\Support\SocialMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StudentPortfolioService
{
    public function getProfileForUser(User $user): StudentAlumni
    {
        $student = StudentAlumni::where('user_id', $user->id)->first();

        if (! $student) {
            // Auto-provision student_alumni record if missing for a student user
            $defaultMajor = Major::where('is_active', true)->first();
            $defaultClass = StandardType::byCategory('class')->where('is_active', true)->first();

            $student = StudentAlumni::create([
                'user_id' => $user->id,
                'major_id' => $defaultMajor?->id ?? 1,
                'class_id' => $defaultClass?->id,
                'nis' => null,
                'graduation_year' => $user->role === 'alumni' ? (int) date('Y') : null,
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        } elseif (empty($student->graduation_year) && $user->role === 'alumni') {
            $student->update([
                'graduation_year' => (int) date('Y'),
                'updated_by' => $user->id,
            ]);
        }

        return $student->load([
            'user',
            'class',
            'major.department',
            'employmentStatus',
            'portfolios.category',
        ]);
    }

    public function getFormOptions(): array
    {
        $majors = Major::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $classes = StandardType::byCategory('class')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name']);

        $employmentStatuses = StandardType::byCategory('employment_status')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name']);

        $portfolioTypes = StandardType::byCategory('portfolio_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'sort_order']);

        $currentYear = (int) date('Y');
        $graduationYears = range($currentYear - 5, $currentYear + 2);

        return [
            'majors' => $majors,
            'classes' => $classes,
            'employment_statuses' => $employmentStatuses,
            'portfolio_types' => $portfolioTypes,
            'graduation_years' => $graduationYears,
        ];
    }

    public function updateProfile(User $user, array $data): StudentAlumni
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'phone' => $data['phone'] ?? $user->phone,
                'updated_by' => $user->id,
            ]);

            $student = $this->getProfileForUser($user);

            $studentUpdates = [
                'updated_by' => $user->id,
            ];

            if (array_key_exists('employment_status_id', $data)) {
                $studentUpdates['employment_status_id'] = $data['employment_status_id'];
            }

            if (array_key_exists('social_media', $data)) {
                $studentUpdates['social_media'] = SocialMedia::storable($data['social_media']);
            }

            $student->update($studentUpdates);

            return $student->fresh([
                'user',
                'class',
                'major',
                'employmentStatus',
                'portfolios.category',
            ]);
        });
    }

    public function uploadPortfolio(User $user, array $data, UploadedFile $file): StudentPortfolio
    {
        return DB::transaction(function () use ($user, $data, $file) {
            $student = $this->getProfileForUser($user);
            $originalFilename = $file->getClientOriginalName();
            $filePath = $file->store('portfolios', 'public');

            $category = StandardType::find($data['category_id']);
            $title = ! empty($data['title']) ? $data['title'] : ($category?->name ?? $originalFilename);

            // Check if portfolio for this category already exists, if so update/replace it
            $existingPortfolio = StudentPortfolio::where('student_alumni_id', $student->id)
                ->where('category_id', $data['category_id'])
                ->first();

            if ($existingPortfolio) {
                if ($existingPortfolio->file_path && Storage::disk('public')->exists($existingPortfolio->file_path)) {
                    Storage::disk('public')->delete($existingPortfolio->file_path);
                }

                $existingPortfolio->update([
                    'title' => $title,
                    'description' => $data['description'] ?? $existingPortfolio->description,
                    'file_path' => $filePath,
                    'original_filename' => $originalFilename,
                    'updated_by' => $user->id,
                ]);

                return $existingPortfolio->load('category');
            }

            $portfolio = StudentPortfolio::create([
                'student_alumni_id' => $student->id,
                'category_id' => $data['category_id'],
                'title' => $title,
                'description' => $data['description'] ?? null,
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            return $portfolio->load('category');
        });
    }

    public function deletePortfolio(User $user, StudentPortfolio $portfolio): bool
    {
        $student = StudentAlumni::where('user_id', $user->id)->first();

        if (! $student || $portfolio->student_alumni_id !== $student->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki hak untuk menghapus dokumen ini.');
        }

        return DB::transaction(function () use ($user, $portfolio) {
            $portfolio->updated_by = $user->id;
            $portfolio->deleted_by = $user->id;
            $portfolio->save();

            return (bool) $portfolio->delete();
        });
    }
}
