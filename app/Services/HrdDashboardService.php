<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\SelectionStage;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HrdDashboardService
{
    public function getDashboardData(int $userId): array
    {
        $company = Company::where('user_id', $userId)->first();
        if (! $company) {
            throw new HttpException(404, 'Data perusahaan untuk akun HRD ini tidak ditemukan.');
        }

        $companyId = $company->id;

        $activeVacanciesQuery = JobVacancy::query()
            ->where('company_id', $companyId)
            ->open();

        $activeVacanciesCount = (clone $activeVacanciesQuery)->count();
        $totalQuota = (int) (clone $activeVacanciesQuery)->sum('quota');

        $applicationsQuery = JobApplication::whereHas('jobVacancy', fn ($q) => $q->where('company_id', $companyId));
        $totalApplicants = (clone $applicationsQuery)->count();

        $pendingReviews = (clone $applicationsQuery)
            ->where(function ($q) {
                $q->whereHas('selectionResult', fn ($sq) => $sq->where('admin_selection_status', 'perlu_review'))
                    ->orWhereDoesntHave('selectionResult');
            })
            ->count();

        $upcomingSchedules = SelectionStage::whereHas('jobVacancy', fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) {
                $q->where('scheduled_at', '>=', now())
                    ->orWhereNull('scheduled_at');
            })
            ->count();

        $acceptedCandidates = (clone $applicationsQuery)
            ->where(function ($q) {
                $q->whereHas('selectionResult', fn ($sq) => $sq->where('decision', 'diterima')->where('status', 'published'))
                    ->orWhereHas('jobPlacement');
            })
            ->count();

        $activeVacanciesList = (clone $activeVacanciesQuery)
            ->with(['majors', 'applications'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function ($v) {
                $majorNames = $v->majors->pluck('name')->implode(', ');

                return [
                    'id' => encrypt((string) $v->id),
                    'title' => $v->title,
                    'department_major' => $majorNames ?: 'Semua Jurusan',
                    'deadline' => $v->deadline ? Carbon::parse($v->deadline)->translatedFormat('d M Y') : 'Fleksibel',
                    'applicant_count' => $v->applications->count(),
                    'quota' => $v->quota ?? 0,
                ];
            })
            ->all();

        $recentApplicants = (clone $applicationsQuery)
            ->with(['studentAlumni.user', 'studentAlumni.major', 'studentAlumni.portfolios', 'jobVacancy'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function ($app) {
                $student = $app->studentAlumni;
                $user = $student?->user;
                $portfolioCount = $student?->portfolios?->count() ?? 0;
                $appliedDate = $app->applied_at ?: $app->created_at;

                return [
                    'id' => encrypt((string) $app->id),
                    'name' => $user?->full_name ?? 'Pelamar',
                    'major' => $student?->major?->name ?? 'Vokasi',
                    'position' => $app->jobVacancy?->position ?? 'Lowongan',
                    'applied_at' => $appliedDate ? Carbon::parse($appliedDate)->translatedFormat('d M Y') : '-',
                    'portfolio' => $portfolioCount > 0 ? "CV & {$portfolioCount} Berkas" : 'Hanya CV',
                ];
            })
            ->all();

        $withPortfoliosCount = (clone $applicationsQuery)
            ->whereHas('studentAlumni.portfolios')
            ->count();

        return [
            'metrics' => [
                'active_vacancies' => $activeVacanciesCount,
                'total_quota' => $totalQuota,
                'total_applicants' => $totalApplicants,
                'pending_reviews' => $pendingReviews,
                'upcoming_schedules' => $upcomingSchedules,
                'accepted_candidates' => $acceptedCandidates,
            ],
            'active_vacancies' => $activeVacanciesList,
            'recent_applicants' => $recentApplicants,
            'document_summary' => [
                'total_applicants' => $totalApplicants,
                'with_portfolio' => $withPortfoliosCount,
                'without_portfolio' => max(0, $totalApplicants - $withPortfoliosCount),
            ],
        ];
    }
}
