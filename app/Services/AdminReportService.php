<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\SelectionStage;
use App\Models\StudentAlumni;
use Carbon\Carbon;

class AdminReportService
{
    /**
     * Ambil opsi filer untuk dropdown laporan
     */
    public function getFilterOptions(): array
    {
        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name]);

        $majors = Major::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($m) => ['value' => (string) $m->id, 'label' => $m->name]);

        $years = StudentAlumni::query()
            ->whereNotNull('graduation_year')
            ->distinct()
            ->orderByDesc('graduation_year')
            ->pluck('graduation_year')
            ->map(fn ($y) => ['value' => (string) $y, 'label' => 'Tahun '.$y]);

        return [
            'companies' => $companies,
            'majors' => $majors,
            'graduation_years' => $years,
        ];
    }

    /**
     * Terapkan filter rentang tanggal fleksibel (start saja, end saja, atau keduanya)
     */
    protected function applyDateRange($query, string $column, array $filters): void
    {
        try {
            if (! empty($filters['start_date'])) {
                $start = Carbon::parse($filters['start_date'])->startOfDay();
                $query->where($column, '>=', $start);
            }
            if (! empty($filters['end_date'])) {
                $end = Carbon::parse($filters['end_date'])->endOfDay();
                $query->where($column, '<=', $end);
            }
        } catch (\Throwable) {
            // Abaikan input tanggal yang tidak valid
        }
    }

    /**
     * 1. Laporan Rekrutmen
     */
    public function getRecruitmentReport(array $filters): array
    {
        $query = JobVacancy::with(['company', 'applications.selectionResult', 'applications.studentAlumni']);

        $this->applyDateRange($query, 'created_at', $filters);

        if (! empty($filters['applicant_type'])) {
            $type = strtolower((string) $filters['applicant_type']);
            $query->withWhereHas('applications', function ($q) use ($type) {
                if ($type === 'siswa') {
                    $q->whereHas('studentAlumni', fn ($sq) => $sq->whereNull('graduation_year'));
                } elseif ($type === 'alumni') {
                    $q->whereHas('studentAlumni', fn ($sq) => $sq->whereNotNull('graduation_year'));
                }
            });
        }

        $vacancies = $query->latest()->get();

        $rows = [];
        $no = 1;
        $totalRegistered = 0;
        $totalPassedFinal = 0;

        foreach ($vacancies as $v) {
            $apps = $v->applications;
            $totalApps = $apps->count();
            $totalRegistered += $totalApps;

            // Lolos seleksi berkas/admin
            $passedAdmin = $apps->filter(function ($a) {
                return $a->selectionResult && $a->selectionResult->admin_selection_status === 'lolos';
            })->count();

            // Lolos tes tulis / wawancara (null-safe, skor desimal dari cast)
            $passedInterview = $apps->filter(function ($a) {
                if (! $a->selectionResult) {
                    return false;
                }
                $interview = (float) ($a->selectionResult->interview_score ?? 0);
                $psycho = (float) ($a->selectionResult->psychotest_score ?? 0);

                return $interview >= 70 || $psycho >= 70;
            })->count();

            // Diterima
            $accepted = $apps->filter(function ($a) {
                return $a->selectionResult && $a->selectionResult->decision === 'diterima';
            })->count();
            $totalPassedFinal += $accepted;

            $passRate = $totalApps > 0 ? round(($accepted / $totalApps) * 100, 1) : 0;

            $rows[] = [
                'no' => $no++,
                'company_name' => $v->company->name ?? 'N/A',
                'job_title' => $v->title,
                'total_applicants' => $totalApps,
                'passed_admin' => $passedAdmin,
                'passed_interview' => $passedInterview,
                'accepted' => $accepted,
                'pass_rate_percentage' => $passRate,
            ];
        }
        $overallRate = $totalRegistered > 0 ? round(($totalPassedFinal / $totalRegistered) * 100, 1) : 0;
        $activeCompaniesCount = Company::where('is_active', true)->count();

        return [
            'metrics' => [
                'total_applicants' => $totalRegistered,
                'total_accepted' => $totalPassedFinal,
                'pass_rate' => $overallRate,
                'active_companies' => $activeCompaniesCount,
            ],
            'data' => $rows,
        ];
    }

    /**
     * 2. Laporan Absensi
     */
    public function getAttendanceReport(array $filters): array
    {
        $query = SelectionStage::with(['jobVacancy.company', 'stageHistories.attendance']);
        if (! empty($filters['company_id'])) {
            $query->whereHas('jobVacancy', function ($q) use ($filters) {
                $q->where('company_id', $filters['company_id']);
            });
        }

        $this->applyDateRange($query, 'scheduled_at', $filters);

        $stages = $query->orderBy('scheduled_at', 'asc')->get();

        $rows = [];
        $no = 1;

        $sosialisasiTarget = 0;
        $sosialisasiPresent = 0;
        $psikotesTarget = 0;
        $psikotesPresent = 0;
        $interviewTarget = 0;
        $interviewPresent = 0;

        foreach ($stages as $stage) {
            $histories = $stage->stageHistories;
            $target = $histories->count();
            $present = $histories->filter(fn ($h) => $h->attendance && $h->attendance->attended_at !== null)->count();
            $absent = max(0, $target - $present);
            $rate = $target > 0 ? round(($present / $target) * 100, 1) : 0;

            $nameLower = strtolower((string) ($stage->name ?? ''));
            if (str_contains($nameLower, 'sosialisasi') || str_contains($nameLower, 'pembekalan') || str_contains($nameLower, 'bkk')) {
                $sosialisasiTarget += $target;
                $sosialisasiPresent += $present;
            } elseif (str_contains($nameLower, 'psiko') || str_contains($nameLower, 'tulis') || str_contains($nameLower, 'tes seleksi') || str_contains($nameLower, 'seleksi')) {
                $psikotesTarget += $target;
                $psikotesPresent += $present;
            } elseif (str_contains($nameLower, 'interview') || str_contains($nameLower, 'wawancara') || str_contains($nameLower, 'hrd')) {
                $interviewTarget += $target;
                $interviewPresent += $present;
            }

            $rows[] = [
                'no' => $no++,
                'agenda_name' => $stage->name ?? 'Tahapan Seleksi',
                'event_date' => $stage->scheduled_at ? $stage->scheduled_at->format('d M Y') : '-',
                'target_participants' => $target,
                'present_valid' => $present,
                'absent' => $absent,
                'attendance_rate' => $rate,
            ];
        }

        $rateOf = fn (int $t, int $p): float => $t > 0 ? round(($p / $t) * 100, 1) : 0.0;

        return [
            'metrics' => [
                'sosialisasi_rate' => $rateOf($sosialisasiTarget, $sosialisasiPresent),
                'psikotes_rate' => $rateOf($psikotesTarget, $psikotesPresent),
                'interview_rate' => $rateOf($interviewTarget, $interviewPresent),
            ],
            'data' => $rows,
        ];
    }

    /**
     * 3. Laporan Keterserapan
     */
    public function getAbsorptionReport(array $filters): array
    {
        $startDate = null;
        $endDate = null;
        try {
            if (! empty($filters['start_date'])) {
                $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            }
            if (! empty($filters['end_date'])) {
                $endDate = Carbon::parse($filters['end_date'])->endOfDay();
            }
        } catch (\Throwable) {
            // Abaikan input tanggal yang tidak valid
        }

        $query = Major::with([
            'studentsAlumni' => function ($q) use ($startDate, $endDate) {
                $q->whereNotNull('graduation_year');
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('created_at', '<=', $endDate);
                }
                $q->with('tracerStudy');
            },
        ]);

        if (! empty($filters['major_id'])) {
            $query->where('id', $filters['major_id']);
        }

        $majors = $query->get();
        $rows = [];
        $no = 1;
        $sumGraduates = 0;
        $sumEmployed = 0;
        $sumStudy = 0;
        $sumBusiness = 0;

        foreach ($majors as $major) {
            $students = $major->studentsAlumni;
            $totalGrads = $students->count();
            $sumGraduates += $totalGrads;

            $employed = 0;
            $study = 0;
            $business = 0;
            $unemployed = 0;

            foreach ($students as $stu) {
                $tracer = $stu->tracerStudy;
                if ($tracer) {
                    match ($tracer->career_status) {
                        'bekerja' => $employed++,
                        'lanjut_studi' => $study++,
                        'wirausaha' => $business++,
                        default => $unemployed++,
                    };
                } else {
                    $unemployed++;
                }
            }

            $absorbed = $employed + $study + $business;
            $rate = $totalGrads > 0 ? round(($absorbed / $totalGrads) * 100, 1) : 0.0;

            $sumEmployed += $employed;
            $sumStudy += $study;
            $sumBusiness += $business;

            $rows[] = [
                'no' => $no++,
                'major_name' => $major->name,
                'total_graduates' => $totalGrads,
                'working_dudi' => $employed,
                'higher_education' => $study,
                'entrepreneur' => $business,
                'unemployed' => $unemployed,
                'absorption_rate' => $rate,
            ];
        }

        $totalAbsorbed = $sumEmployed + $sumStudy + $sumBusiness;
        $overallAbsorption = $sumGraduates > 0 ? round(($totalAbsorbed / $sumGraduates) * 100, 1) : 0.0;
        $dudiPercentage = $sumGraduates > 0 ? round(($sumEmployed / $sumGraduates) * 100, 1) : 0.0;
        $studyBizPercentage = $sumGraduates > 0 ? round((($sumStudy + $sumBusiness) / $sumGraduates) * 100, 1) : 0.0;

        // Keterserapan kelas 12 = siswa aktif (belum lulus) yang sudah terserap via penempatan
        $studentsActive = StudentAlumni::student()->count();
        $placementQuery = JobPlacement::whereHas('studentAlumni', fn ($q) => $q->whereNull('graduation_year'));
        $this->applyDateRange($placementQuery, 'start_date', $filters);
        $studentsPlaced = $placementQuery->distinct()->count('student_alumni_id');
        $class12Rate = $studentsActive > 0 ? round(($studentsPlaced / $studentsActive) * 100, 1) : 0.0;

        return [
            'metrics' => [
                'class_12_rate' => $class12Rate,
                'alumni_rate' => $overallAbsorption,
                'working_dudi_rate' => $dudiPercentage,
                'study_entrepreneur_rate' => $studyBizPercentage,
            ],
            'data' => $rows,
        ];
    }

    /**
     * 4. Laporan Tracer Study & Retensi
     */
    public function getTracerStudyReport(array $filters): array
    {
        $yearsQuery = StudentAlumni::query()
            ->whereNotNull('graduation_year');

        if (! empty($filters['graduation_year'])) {
            $yearsQuery->where('graduation_year', $filters['graduation_year']);
        }

        $this->applyDateRange($yearsQuery, 'created_at', $filters);

        $gradYears = $yearsQuery->distinct()
            ->orderByDesc('graduation_year')
            ->pluck('graduation_year');

        $rows = [];
        $no = 1;

        $allWaitingMonths = [];
        $allCompanies = [];
        $allSectors = [];
        $allRegions = [];

        foreach ($gradYears as $year) {
            $alumni = StudentAlumni::where('graduation_year', $year)
                ->with(['tracerStudy', 'jobPlacements.placementStatus'])
                ->get();

            $tracers = $alumni->pluck('tracerStudy')->filter();
            $placements = $alumni->flatMap->jobPlacements;

            // Rata-rata masa tunggu dari kolom waiting_time_months (bulan) atau fallback string tracerStudy
            $waitingMonths = $alumni->map(function ($stu) {
                if ($stu->waiting_time_months !== null) {
                    return (int) $stu->waiting_time_months;
                }
                if ($stu->tracerStudy?->waiting_period) {
                    $digits = preg_replace('/[^0-9]/', '', (string) $stu->tracerStudy->waiting_period);
                    return $digits !== '' ? (int) $digits : null;
                }
                return null;
            })->filter(fn ($v) => $v !== null);

            foreach ($waitingMonths as $m) {
                $allWaitingMonths[] = $m;
            }
            $avgMonths = $waitingMonths->count() > 0 ? round($waitingMonths->avg(), 1) : 0.0;
            $waitingAvg = $waitingMonths->count() > 0 ? $avgMonths.' Bulan' : '-';

            // Retensi dari JobPlacement per alumni
            $retentionOf = function (int $months) use ($placements): string {
                $eligible = $placements->filter(fn ($p) => $p->start_date && now()->gte($p->start_date->copy()->addMonths($months)));
                if ($eligible->isEmpty()) {
                    return '-';
                }
                $retained = $eligible->filter(fn ($p) => $p->isRetainedAtMonths($months))->count();

                return round(($retained / $eligible->count()) * 100, 1).'%';
            };

            $cityCounts = [];
            foreach ($tracers as $t) {
                if ($t->company_name) {
                    $allCompanies[trim(strtolower($t->company_name))] = true;
                }
                if ($t->job_location) {
                    $cityCounts[$t->job_location] = ($cityCounts[$t->job_location] ?? 0) + 1;
                    $allRegions[$t->job_location] = true;
                }
                if ($t->company_sector) {
                    $allSectors[$t->company_sector] = true;
                }
            }

            foreach ($placements as $p) {
                if ($p->company_id) {
                    $allCompanies['cid_'.$p->company_id] = true;
                }
            }

            arsort($cityCounts);
            $dominantRegion = ! empty($cityCounts) ? implode(' & ', array_slice(array_keys($cityCounts), 0, 2)) : '-';

            $rows[] = [
                'no' => $no++,
                'graduation_year' => 'Tahun '.$year,
                'waiting_time_avg' => $waitingAvg,
                'retention_3_months' => $retentionOf(3),
                'retention_6_months' => $retentionOf(6),
                'retention_12_months' => $retentionOf(12),
                'dominant_region' => $dominantRegion,
            ];
        }

        $overallAvg = count($allWaitingMonths) > 0 ? round(array_sum($allWaitingMonths) / count($allWaitingMonths), 1).' Bulan' : '-';
        $companiesCount = max(count($allCompanies), count($allSectors));

        return [
            'metrics' => [
                'avg_waiting_time' => $overallAvg,
                'industries_count' => $companiesCount,
                'sectors_count' => count($allSectors),
                'regions_count' => count($allRegions),
            ],
            'data' => $rows,
        ];
    }
}
