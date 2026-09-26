<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\JobApplication;
use App\Models\JobPlacement;
use App\Models\JobVacancy;
use App\Models\SelectionResult;
use App\Models\StudentAlumni;
use App\Models\TracerStudy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
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
        $counts = DB::table('students_alumni as sa')
            ->join('users as u', 'u.id', '=', 'sa.user_id')
            ->whereNull('sa.deleted_at')
            ->whereNull('u.deleted_at')
            ->selectRaw("
                COUNT(CASE WHEN u.role = 'siswa' THEN 1 END) as active_students,
                COUNT(CASE WHEN sa.graduation_year IS NOT NULL THEN 1 END) as total_alumni
            ")
            ->first();

        $activeStudents = (int) ($counts?->active_students ?? 0);
        $totalAlumni = (int) ($counts?->total_alumni ?? 0);

        $activeVacancies = JobVacancy::query()
            ->open()
            ->count();

        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $applicantsThisMonth = JobApplication::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('applied_at', [$start, $end])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->whereNull('applied_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->count();

        $placedWorkers = JobPlacement::query()
            ->count();

        $absorbedAlumni = TracerStudy::query()
            ->whereIn('career_status', ['bekerja', 'wirausaha', 'lanjut_studi'])
            ->count();

        $absorptionRate = $totalAlumni > 0 ? round(($absorbedAlumni / $totalAlumni) * 100, 1) : 0.0;

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
        $now = Carbon::now();
        $rangeStart = $now->copy()->subMonths(5)->startOfMonth();
        $rangeEnd = $now->copy()->endOfMonth();

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $appMonthExpr = $isSqlite
            ? "strftime('%Y-%m', COALESCE(applied_at, created_at))"
            : "to_char(COALESCE(applied_at, created_at), 'YYYY-MM')";

        $applications = JobApplication::query()
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('applied_at', [$rangeStart, $rangeEnd])
                    ->orWhere(function ($sub) use ($rangeStart, $rangeEnd) {
                        $sub->whereNull('applied_at')
                            ->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                    });
            })
            ->selectRaw("{$appMonthExpr} as ym, count(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->all();

        $selMonthExpr = $isSqlite
            ? "strftime('%Y-%m', updated_at)"
            : "to_char(updated_at, 'YYYY-MM')";

        $acceptedResults = SelectionResult::query()
            ->where('decision', 'diterima')
            ->where('status', 'published')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd])
            ->selectRaw("{$selMonthExpr} as ym, count(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->all();

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $targetMonth = $now->copy()->subMonths($i);
            $ymKey = $targetMonth->format('Y-m');

            $months[] = [
                'month' => $monthNames[$targetMonth->month] ?? $targetMonth->format('M'),
                'melamar' => (int) ($applications[$ymKey] ?? 0),
                'diterima' => (int) ($acceptedResults[$ymKey] ?? 0),
            ];
        }

        return $months;
    }

    public function getDepartmentDistribution(): array
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->get();

        $placementCounts = DB::table('job_placements as jp')
            ->join('students_alumni as sa', 'sa.id', '=', 'jp.student_alumni_id')
            ->join('majors as m', 'm.id', '=', 'sa.major_id')
            ->whereNull('jp.deleted_at')
            ->whereNull('sa.deleted_at')
            ->whereNull('m.deleted_at')
            ->select('m.department_id', DB::raw('count(jp.id) as aggregate'))
            ->groupBy('m.department_id')
            ->pluck('aggregate', 'm.department_id')
            ->all();

        $totalPlacements = array_sum($placementCounts);
        $result = [];

        foreach ($departments as $dept) {
            $count = (int) ($placementCounts[$dept->id] ?? 0);
            $percentage = $totalPlacements > 0 ? round(($count / $totalPlacements) * 100, 1) : 0.0;

            $result[] = [
                'name' => $dept->name,
                'code' => strtoupper((string) $dept->code),
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $result;
    }
}
