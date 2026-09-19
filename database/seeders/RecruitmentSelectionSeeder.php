<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApplicationStageHistory;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionResult;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecruitmentSelectionSeeder extends Seeder
{
    /**
     * Seed selection pipeline: stages -> applications -> histories/attendances -> results
     * Idempotent: truncates dependent tables before re-seeding.
     */
    public function run(): void
    {
        $vacancies = JobVacancy::orderBy('id')->get();
        $students = StudentAlumni::with(['user', 'major'])->orderBy('id')->get();

        if ($vacancies->isEmpty()) {
            $this->command?->warn('RecruitmentSelectionSeeder: No job_vacancies found. Run JobVacancySeeder first.');

            return;
        }

        if ($students->isEmpty()) {
            $this->command?->warn('RecruitmentSelectionSeeder: No students_alumni found. Run StudentAlumniSeeder first.');

            return;
        }

        // Resolve StandardTypes with fallback category names.
        $stageTypeMap = $this->resolveTypeMap(['stage_type', 'selection_stage']);
        $jobAppStatusMap = $this->resolveTypeMap(['job_application_status']);
        $stageHistoryStatusMap = $this->resolveTypeMap(['application_stage_status']);
        $attendanceStatusMap = $this->resolveTypeMap(['attendance_status']);

        $adminUserId = User::whereIn('role', ['admin', 'superadmin', 'hrd'])->value('id') ?? User::value('id');

        // Fallback guards
        $statusPendingId = $jobAppStatusMap['pending'] ?? null;
        $statusInProgressId = $jobAppStatusMap['in_progress'] ?? $statusPendingId;
        $statusAcceptedId = $jobAppStatusMap['accepted'] ?? $statusInProgressId;
        $statusRejectedId = $jobAppStatusMap['rejected'] ?? $statusPendingId;

        $historyScheduledId = $stageHistoryStatusMap['scheduled'] ?? null;
        $historyPassedId = $stageHistoryStatusMap['passed'] ?? $historyScheduledId;
        $historyFailedId = $stageHistoryStatusMap['failed'] ?? $historyScheduledId;
        $historyAbsentId = $stageHistoryStatusMap['absent'] ?? $historyScheduledId;

        $presentId = $attendanceStatusMap['present'] ?? null;
        $absentId = $attendanceStatusMap['absent'] ?? $presentId;
        $leaveId = $attendanceStatusMap['leave'] ?? $absentId;

        DB::transaction(function () use (
            $vacancies,
            $students,
            $stageTypeMap,
            $adminUserId,
            $statusPendingId,
            $statusInProgressId,
            $statusAcceptedId,
            $statusRejectedId,
            $historyScheduledId,
            $historyPassedId,
            $historyFailedId,
            $historyAbsentId,
            $presentId,
            $absentId,
            $leaveId
        ): void {
            // --- Cleanup in FK order (child -> parent) ---
            RecruitmentAttendance::query()->delete();
            ApplicationStageHistory::query()->delete();
            SelectionResult::query()->delete();
            JobApplication::withTrashed()->forceDelete();
            SelectionStage::withTrashed()->forceDelete();

            // --- 1. Selection Stages: 3 per vacancy (use first 5 vacancies for richer data) ---
            $targetVacancies = $vacancies->take(5);
            if ($targetVacancies->count() < 3) {
                $targetVacancies = $vacancies;
            }

            $blueprints = [
                [
                    'name' => 'Tahap 1 - Administrasi Berkas',
                    'code' => 'administration',
                    'sequence_order' => 1,
                    'description' => 'Verifikasi kelengkapan berkas: ijazah, transkrip, CV, portofolio, dan surat lamaran.',
                    'location' => 'Ruang BKK SMKN 1 Gresik',
                    'days_offset' => -14,
                    'minimum_score' => 70.00,
                ],
                [
                    'name' => 'Tahap 2 - Tes Logika & Psikotes',
                    'code' => 'psychological_test',
                    'fallback_codes' => ['written_test', 'psychological_test', 'coding_test'],
                    'sequence_order' => 2,
                    'description' => 'Tes potensi akademik, logika, dan psikotes untuk mengukur kesiapan kerja.',
                    'location' => 'Lab Komputer SKARIGA Lt.2',
                    'days_offset' => -7,
                    'minimum_score' => 75.00,
                ],
                [
                    'name' => 'Tahap 3 - Wawancara HRD & User',
                    'code' => 'interview_hrd',
                    'fallback_codes' => ['interview_hrd', 'interview_user'],
                    'sequence_order' => 3,
                    'description' => 'Wawancara mendalam dengan HRD dan user untuk menilai kompetensi & attitude.',
                    'location' => 'Ruang Interview Gedung BKK / Online via Zoom Meeting',
                    'days_offset' => 0,
                    'minimum_score' => 75.00,
                ],
            ];

            /** @var array<int, array<int, SelectionStage>> $stagesByVacancy */
            $stagesByVacancy = [];

            foreach ($targetVacancies as $vacancy) {
                $stagesByVacancy[$vacancy->id] = [];
                foreach ($blueprints as $bp) {
                    $stageTypeId = $this->resolveStageTypeId($bp, $stageTypeMap);
                    $scheduledAt = Carbon::now('Asia/Jakarta')->addDays((int) $bp['days_offset'])->setHour(9)->setMinute(0)->setSecond(0);

                    $stage = SelectionStage::create([
                        'job_vacancy_id' => $vacancy->id,
                        'stage_type_id' => $stageTypeId,
                        'name' => $bp['name'],
                        'sequence_order' => $bp['sequence_order'],
                        'description' => $bp['description'],
                        'minimum_score' => $bp['minimum_score'],
                        'scheduled_at' => $scheduledAt,
                        'location' => $bp['location'],
                        'created_by' => $adminUserId,
                        'updated_by' => $adminUserId,
                    ]);
                    $stagesByVacancy[$vacancy->id][] = $stage;
                }
            }

            // --- 2. Job Applications + Histories + Attendances + Results ---
            $perVacancy = 6; // 5 vacancies * 6 = 30 applicants (covers pagination, filters)
            $studentsList = $students->values();
            $globalIdx = 0;

            // Pre-fetch HRD assessor for history
            $assessorId = User::where('role', 'hrd')->value('id') ?? $adminUserId;

            foreach ($targetVacancies as $vacancy) {
                $stages = $stagesByVacancy[$vacancy->id];
                // Guard: if vacancy had no stages (edge), skip
                if (count($stages) < 3) {
                    continue;
                }

                for ($i = 0; $i < $perVacancy; $i++) {
                    // Round-robin students to avoid duplicate (vacancy, student) if possible
                    $student = $studentsList[($globalIdx % $studentsList->count())];
                    // If student already applied to this vacancy, pick next
                    $attempt = 0;
                    while (
                        JobApplication::where('job_vacancy_id', $vacancy->id)
                            ->where('student_alumni_id', $student->id)->exists() && $attempt < $studentsList->count()
                    ) {
                        $globalIdx++;
                        $student = $studentsList[($globalIdx % $studentsList->count())];
                        $attempt++;
                    }

                    $scenario = $globalIdx % 5;
                    // applied_at spread last 30 days
                    $appliedAt = Carbon::now('Asia/Jakarta')->subDays(5 + ($globalIdx % 25))->setHour(10)->setMinute(random_int(0, 59));

                    // Determine current_stage, status, and scores per scenario
                    $currentStage = null;
                    $jobStatusId = $statusPendingId;
                    $adminSelectionStatus = 'lolos';
                    $decision = 'pending';
                    $resultStatus = 'draft';
                    $psychotestScore = null;
                    $interviewScore = null;
                    $mcuScore = null;
                    $notes = null;

                    switch ($scenario) {
                        case 0: // Tidak lolos administrasi - gagal di tahap 1, hadir tapi nilai rendah
                            $currentStage = $stages[0];
                            $jobStatusId = $statusRejectedId;
                            $adminSelectionStatus = 'tidak_lolos';
                            $decision = 'tidak_diterima';
                            $resultStatus = 'published';
                            $psychotestScore = $this->randDecimal(45, 60);
                            $interviewScore = $this->randDecimal(40, 55);
                            $mcuScore = $this->randDecimal(50, 65);
                            $notes = 'Berkas tidak lengkap / tidak memenuhi kualifikasi administrasi.';
                            break;
                        case 1: // Lolos admin, progres tahap 2 (scheduled, belum selesaikan wawancara)
                            $currentStage = $stages[1];
                            $jobStatusId = $statusInProgressId;
                            $adminSelectionStatus = 'lolos';
                            $decision = 'pending';
                            $resultStatus = 'draft';
                            $psychotestScore = $this->randDecimal(75, 88);
                            $interviewScore = null;
                            $mcuScore = null;
                            $notes = 'Lolos administrasi, menunggu jadwal psikotes.';
                            break;
                        case 2: // Cadangan - nilai menengah, semua tahap dilalui, hadir
                            $currentStage = $stages[2];
                            $jobStatusId = $statusInProgressId;
                            $adminSelectionStatus = 'lolos';
                            $decision = 'cadangan';
                            $resultStatus = 'published';
                            $psychotestScore = $this->randDecimal(70, 78);
                            $interviewScore = $this->randDecimal(70, 76);
                            $mcuScore = $this->randDecimal(74, 80);
                            $notes = 'Nilai memenuhi KKM, masuk daftar cadangan.';
                            break;
                        case 3: // Diterima - nilai tinggi, semua tahap lolos hadir
                            $currentStage = $stages[2];
                            $jobStatusId = $statusAcceptedId;
                            $adminSelectionStatus = 'lolos';
                            $decision = 'diterima';
                            $resultStatus = 'published';
                            $psychotestScore = $this->randDecimal(85, 96);
                            $interviewScore = $this->randDecimal(84, 94);
                            $mcuScore = $this->randDecimal(85, 95);
                            $notes = 'Selamat! Kandidat dinyatakan diterima dan siap penempatan.';
                            break;
                        case 4: // Pending / Belum presensi & Tidak hadir (bergantian)
                            $currentStage = $stages[0];
                            $jobStatusId = $statusPendingId;
                            $adminSelectionStatus = 'lolos';
                            $decision = 'pending';
                            $resultStatus = 'draft';
                            $psychotestScore = null;
                            $interviewScore = null;
                            $mcuScore = null;
                            // Alternate between belum presensi (no attendance) vs tidak_hadir (absent)
                            $notes = ($globalIdx % 2 === 0)
                                ? 'Pelamar tidak hadir pada jadwal administrasi (Alpa).'
                                : 'Pelamar belum melakukan presensi.';
                            break;
                    }

                    $finalScore = $this->calcFinalScore($psychotestScore, $interviewScore, $mcuScore);

                    $application = JobApplication::create([
                        'job_vacancy_id' => $vacancy->id,
                        'student_alumni_id' => $student->id,
                        'status_id' => $jobStatusId,
                        'current_stage_id' => $currentStage?->id,
                        'applied_at' => $appliedAt,
                        'notes' => $notes,
                        'created_by' => $adminUserId,
                        'updated_by' => $adminUserId,
                    ]);

                    // --- Stage Histories + Attendances ---
                    $this->createHistoriesForScenario(
                        scenario: $scenario,
                        globalIdx: $globalIdx,
                        application: $application,
                        stages: $stages,
                        historyScheduledId: $historyScheduledId,
                        historyPassedId: $historyPassedId,
                        historyFailedId: $historyFailedId,
                        historyAbsentId: $historyAbsentId,
                        presentId: $presentId,
                        absentId: $absentId,
                        leaveId: $leaveId,
                        assessorId: $assessorId,
                        adminUserId: $adminUserId
                    );

                    // --- Selection Result ---
                    SelectionResult::create([
                        'job_application_id' => $application->id,
                        'admin_selection_status' => $adminSelectionStatus,
                        'psychotest_score' => $psychotestScore,
                        'interview_score' => $interviewScore,
                        'mcu_score' => $mcuScore,
                        'final_score' => $finalScore,
                        'decision' => $decision,
                        'status' => $resultStatus,
                        'notes' => $notes,
                        'created_by' => $adminUserId,
                        'updated_by' => $adminUserId,
                    ]);

                    $globalIdx++;
                }
            }

            $this->command?->info('RecruitmentSelectionSeeder: Seeded '.SelectionStage::count().' stages, '.JobApplication::count().' applications with results & histories.');
        });
    }

    /**
     * Resolve StandardType map for given categories (first match per code wins).
     *
     * @param  string[]  $categories
     * @return array<string, int> code => id
     */
    private function resolveTypeMap(array $categories): array
    {
        $map = [];
        foreach ($categories as $cat) {
            $types = StandardType::byCategory($cat)->get();
            foreach ($types as $t) {
                if (! isset($map[$t->code])) {
                    $map[$t->code] = $t->id;
                }
            }
            if (! empty($map)) {
                // If we found for this category, keep merging but don't override existing codes
                continue;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @param  array<string, int>  $stageTypeMap
     */
    private function resolveStageTypeId(array $blueprint, array $stageTypeMap): ?int
    {
        $code = $blueprint['code'] ?? null;
        if ($code && isset($stageTypeMap[$code])) {
            return $stageTypeMap[$code];
        }
        foreach ($blueprint['fallback_codes'] ?? [] as $fallback) {
            if (isset($stageTypeMap[$fallback])) {
                return $stageTypeMap[$fallback];
            }
        }

        // Return first available stage type as last resort
        return ! empty($stageTypeMap) ? (int) array_values($stageTypeMap)[0] : null;
    }

    private function randDecimal(int $min, int $max): float
    {
        $int = random_int($min * 100, $max * 100);

        return round($int / 100, 2);
    }

    private function calcFinalScore(?float $p, ?float $i, ?float $m): ?float
    {
        $scores = array_filter([$p, $i, $m], fn ($v) => $v !== null);
        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Create ApplicationStageHistory + RecruitmentAttendance rows realistic per scenario.
     *
     * @param  array<int, SelectionStage>  $stages  [0=>admin, 1=>psikotes, 2=>wawancara]
     */
    private function createHistoriesForScenario(
        int $scenario,
        int $globalIdx,
        JobApplication $application,
        array $stages,
        ?int $historyScheduledId,
        ?int $historyPassedId,
        ?int $historyFailedId,
        ?int $historyAbsentId,
        ?int $presentId,
        ?int $absentId,
        ?int $leaveId,
        ?int $assessorId,
        ?int $adminUserId
    ): void {
        // Helper to create attendance
        $makeAttendance = function (ApplicationStageHistory $history, ?int $attendanceStatusId, bool $isPresent): void {
            if ($attendanceStatusId === null && ! $isPresent) {
                return;
            }
            $attendedAt = null;
            if ($isPresent) {
                $attendedAt = $history->selectionStage->scheduled_at
                    ? Carbon::parse($history->selectionStage->scheduled_at)->addMinutes(random_int(0, 120))
                    : Carbon::now('Asia/Jakarta');
            }
            RecruitmentAttendance::create([
                'stage_history_id' => $history->id,
                'attendance_status_id' => $attendanceStatusId,
                'attended_at' => $attendedAt,
                'validation_status' => $attendanceStatusId ? 'verified' : 'pending',
            ]);
        };

        switch ($scenario) {
            case 0: // Gagal admin - 1 history failed + hadir
                $h1 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[0]->id,
                    'status_id' => $historyFailedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(50, 62),
                    'notes' => 'Berkas tidak memenuhi syarat minimum.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h1, $presentId, true);
                break;

            case 1: // Lolos admin -> psikotes scheduled (belum presensi for odd, hadir for even to test both)
                $h1 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[0]->id,
                    'status_id' => $historyPassedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(75, 88),
                    'notes' => 'Administrasi lolos.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h1, $presentId, true);

                $h2 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[1]->id,
                    'status_id' => $historyScheduledId,
                    'assessor_id' => null,
                    'score' => null,
                    'notes' => 'Menunggu pelaksanaan psikotes.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                // Odd idx -> belum presensi (no attendance row), Even -> present but scheduled
                if ($globalIdx % 2 === 0) {
                    $makeAttendance($h2, null, false); // belum
                    RecruitmentAttendance::create([
                        'stage_history_id' => $h2->id,
                        'attendance_status_id' => null,
                        'attended_at' => null,
                        'validation_status' => 'pending',
                    ]);
                }
                break;

            case 2: // Cadangan - 3 histories all passed hadir
                $h1 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[0]->id,
                    'status_id' => $historyPassedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(72, 78),
                    'notes' => 'Administrasi lolos.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h1, $presentId, true);

                $h2 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[1]->id,
                    'status_id' => $historyPassedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(70, 78),
                    'notes' => 'Psikotes lolos (cadangan).',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h2, $presentId, true);

                $h3 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[2]->id,
                    'status_id' => $historyPassedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(70, 76),
                    'notes' => 'Wawancara cukup baik, masuk cadangan.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h3, $presentId, true);
                break;

            case 3: // Diterima - 3 histories all passed hadir
                $h1 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[0]->id,
                    'status_id' => $historyPassedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(85, 92),
                    'notes' => 'Administrasi sangat baik.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h1, $presentId, true);

                $h2 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[1]->id,
                    'status_id' => $historyPassedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(86, 95),
                    'notes' => 'Psikotes sangat baik.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h2, $presentId, true);

                $h3 = ApplicationStageHistory::create([
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stages[2]->id,
                    'status_id' => $historyPassedId,
                    'assessor_id' => $assessorId,
                    'score' => $this->randDecimal(84, 94),
                    'notes' => 'Wawancara excellent, direkomendasikan diterima.',
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
                $makeAttendance($h3, $presentId, true);
                break;

            case 4: // Pending - alternate belum vs tidak_hadir
                if ($globalIdx % 2 === 0) {
                    // Tidak hadir (absent) - has attendance with absent code
                    $h1 = ApplicationStageHistory::create([
                        'job_application_id' => $application->id,
                        'selection_stage_id' => $stages[0]->id,
                        'status_id' => $historyAbsentId,
                        'assessor_id' => null,
                        'score' => null,
                        'notes' => 'Tidak hadir tanpa keterangan.',
                        'created_by' => $adminUserId,
                        'updated_by' => $adminUserId,
                    ]);
                    $statusId = ($globalIdx % 4 === 0) ? $absentId : $leaveId;
                    RecruitmentAttendance::create([
                        'stage_history_id' => $h1->id,
                        'attendance_status_id' => $statusId,
                        'attended_at' => null,
                        'validation_status' => 'verified',
                    ]);
                } else {
                    // Belum presensi - scheduled without attendance
                    $h1 = ApplicationStageHistory::create([
                        'job_application_id' => $application->id,
                        'selection_stage_id' => $stages[0]->id,
                        'status_id' => $historyScheduledId,
                        'assessor_id' => null,
                        'score' => null,
                        'notes' => 'Menunggu verifikasi berkas.',
                        'created_by' => $adminUserId,
                        'updated_by' => $adminUserId,
                    ]);
                    // Intentionally no attendance row -> service maps to 'belum'
                }
                break;
        }
    }
}
