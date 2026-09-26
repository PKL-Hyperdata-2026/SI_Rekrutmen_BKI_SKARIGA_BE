<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\JobApplication;
use App\Models\JobPlacement;
use App\Models\JobVacancy;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionResult;
use App\Models\StudentAlumni;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function __construct(
        protected AdminReportService $reportService
    ) {}

    public function getDashboardData(): array
    {
        return [
            'metrics' => $this->getMetrics(),
            'recruitment_chart' => $this->getRecruitmentChart(),
            'department_distribution' => $this->getDepartmentDistribution(),
        ];
    }

    public function getMetrics(): array
    {
        $activeStudents = StudentAlumni::query()
            ->whereHas('user', fn ($q) => $q->where('role', 'siswa'))
            ->count();

        $totalAlumni = StudentAlumni::query()
            ->alumni()
            ->count();

        $activeVacancies = JobVacancy::query()
            ->open()
            ->count();

        $now = Carbon::now();
        $applicantsThisMonth = JobApplication::query()
            ->where(function ($q) use ($now) {
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $q->whereBetween('applied_at', [$start, $end])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->whereNull('applied_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->count();

        $placedWorkers = JobPlacement::query()
            ->whereNull('deleted_at')
            ->count();

        $absorptionReport = $this->reportService->getAbsorptionReport([]);
        $absorptionRate = (float) ($absorptionReport['metrics']['alumni_rate'] ?? 0.0);

        return [
            'active_students' => $activeStudents,
            'total_alumni' => $totalAlumni,
            'active_vacancies' => $activeVacancies,
            'applicants_this_month' => $applicantsThisMonth,
            'placed_workers' => $placedWorkers,
            'absorption_rate' => $absorptionRate,
        ];
    }

    public function getRecruitmentChart(): array
    {
        $months = [];
        $now = Carbon::now();

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        for ($i = 5; $i >= 0; $i--) {
            $targetMonth = $now->copy()->subMonths($i);
            $start = $targetMonth->copy()->startOfMonth();
            $end = $targetMonth->copy()->endOfMonth();

            $melamarCount = JobApplication::query()
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('applied_at', [$start, $end])
                        ->orWhere(function ($sub) use ($start, $end) {
                            $sub->whereNull('applied_at')
                                ->whereBetween('created_at', [$start, $end]);
                        });
                })
                ->count();

            $diterimaCount = SelectionResult::query()
                ->where('decision', 'diterima')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $months[] = [
                'month' => $monthNames[$targetMonth->month] ?? $targetMonth->format('M'),
                'melamar' => $melamarCount,
                'diterima' => $diterimaCount,
            ];
        }

        return $months;
    }

    public function getDepartmentDistribution(): array
    {
        $palette = [
            'TIK' => '#7C3AED',
            'MESIN' => '#F43F5E',
            'OTOMOTIF' => '#0EA5E9',
            'ELEKTRO' => '#10B981',
        ];
        $fallbackColors = ['#F59E0B', '#6366F1', '#EC4899', '#14B8A6'];

        $departments = Department::query()
            ->where('is_active', true)
            ->get();

        $placementCounts = DB::table('job_placements as jp')
            ->join('students_alumni as sa', 'sa.id', '=', 'jp.student_alumni_id')
            ->join('majors as m', 'm.id', '=', 'sa.major_id')
            ->whereNull('jp.deleted_at')
            ->select('m.department_id', DB::raw('count(jp.id) as aggregate'))
            ->groupBy('m.department_id')
            ->pluck('aggregate', 'm.department_id')
            ->all();

        $totalPlacements = array_sum($placementCounts);

        $colorIdx = 0;
        $result = [];

        foreach ($departments as $dept) {
            $count = (int) ($placementCounts[$dept->id] ?? 0);
            $percentage = $totalPlacements > 0 ? round(($count / $totalPlacements) * 100, 1) : 0.0;
            $code = strtoupper((string) $dept->code);
            $color = $palette[$code] ?? ($fallbackColors[$colorIdx++ % count($fallbackColors)]);

            $result[] = [
                'name' => $dept->name,
                'code' => $code,
                'count' => $count,
                'percentage' => $percentage,
                'color' => $color,
            ];
        }

        return $result;
    }
}
