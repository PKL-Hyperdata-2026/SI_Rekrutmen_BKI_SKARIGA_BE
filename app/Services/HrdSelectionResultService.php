<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApplicationStageHistory;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\SelectionResult;
use App\Models\StandardType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HrdSelectionResultService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($companyId, $filters);

        $query->with([
            'studentAlumni.user',
            'studentAlumni.major',
            'jobVacancy',
            'selectionResult',
            'status',
            'stageHistories.attendance',
        ]);

        $sortBy = (string) ($filters['sort_by'] ?? 'applied_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->select('job_applications.*');

        if ($sortBy === 'name') {
            $query->leftJoin('students_alumni as sa_sort', 'sa_sort.id', '=', 'job_applications.student_alumni_id');
            $query->leftJoin('users as u_sort', 'u_sort.id', '=', 'sa_sort.user_id');
            $query->orderBy('u_sort.full_name', $sortDir)->orderBy('job_applications.id', $sortDir);
        } else {
            $query->orderBy('job_applications.applied_at', $sortDir)->orderBy('job_applications.id', $sortDir);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function getSummary(int $companyId, array $filters = []): array
    {
        $baseFilters = $filters;
        unset($baseFilters['decision'], $baseFilters['status']);

        $base = $this->buildBaseQuery($companyId, $baseFilters);

        $total = (clone $base)->count();

        $lolos = (clone $base)->whereHas('selectionResult', function (Builder $q): void {
            $q->where('decision', 'diterima');
        })->count();

        $gagal = (clone $base)->whereHas('selectionResult', function (Builder $q): void {
            $q->where('decision', 'tidak_diterima');
        })->count();

        $cadangan = (clone $base)->whereHas('selectionResult', function (Builder $q): void {
            $q->where('decision', 'cadangan');
        })->count();

        return [
            'total' => $total,
            'lolos' => $lolos,
            'gagal' => $gagal,
            'cadangan' => $cadangan,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(int $companyId): array
    {
        $vacancies = JobVacancy::where('company_id', $companyId)
            ->select('id', 'title', 'position')
            ->orderBy('title')
            ->get()
            ->map(fn (JobVacancy $v): array => [
                'value' => $v->id,
                'label' => $v->position !== '' ? (string) $v->position : (string) $v->title,
                'extra' => ['title' => $v->title],
            ])
            ->values()
            ->all();

        return [
            'vacancies' => $vacancies,
            'decisions' => [
                ['value' => 'semua', 'label' => 'Semua Keputusan'],
                ['value' => 'diterima', 'label' => 'Diterima'],
                ['value' => 'tidak_diterima', 'label' => 'Tidak Diterima'],
                ['value' => 'cadangan', 'label' => 'Cadangan'],
                ['value' => 'pending', 'label' => 'Pending'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveEvaluation(int $companyId, int $applicationId, array $data, ?UploadedFile $letterFile, int $hrdUserId): JobApplication
    {
        return DB::transaction(function () use ($companyId, $applicationId, $data, $letterFile, $hrdUserId): JobApplication {
            $application = JobApplication::with(['jobVacancy', 'selectionResult', 'studentAlumni.user'])
                ->where('id', $applicationId)
                ->whereHas('jobVacancy', fn (Builder $q) => $q->where('company_id', $companyId))
                ->first();

            if (! $application) {
                throw new HttpException(404, 'Data lamaran tidak ditemukan atau bukan milik perusahaan Anda.');
            }

            $scores = [];
            if (isset($data['psychotest_score']) && $data['psychotest_score'] !== '') {
                $scores[] = (float) $data['psychotest_score'];
            }
            if (isset($data['interview_score']) && $data['interview_score'] !== '') {
                $scores[] = (float) $data['interview_score'];
            }
            if (isset($data['mcu_score']) && $data['mcu_score'] !== '') {
                $scores[] = (float) $data['mcu_score'];
            }

            $finalScore = isset($data['final_score']) && $data['final_score'] !== ''
                ? (float) $data['final_score']
                : ($scores !== [] ? round(array_sum($scores) / count($scores), 2) : null);

            $decision = (string) ($data['decision'] ?? 'pending');
            $notes = isset($data['notes']) ? (string) $data['notes'] : null;

            $result = SelectionResult::firstOrNew(['job_application_id' => $application->id]);
            if (! $result->exists) {
                $result->created_by = $hrdUserId;
            }

            if ($letterFile) {
                $letterPath = $letterFile->store('placement_letters', 'public');
                $result->letter_path = $letterPath;
            }

            $result->admin_selection_status = (string) ($data['admin_selection_status'] ?? $result->admin_selection_status ?? 'lolos');
            $result->psychotest_score = isset($data['psychotest_score']) && $data['psychotest_score'] !== '' ? (float) $data['psychotest_score'] : null;
            $result->interview_score = isset($data['interview_score']) && $data['interview_score'] !== '' ? (float) $data['interview_score'] : null;
            $result->mcu_score = isset($data['mcu_score']) && $data['mcu_score'] !== '' ? (float) $data['mcu_score'] : null;
            $result->final_score = $finalScore;
            $result->decision = $decision;
            $result->notes = $notes;
            $result->updated_by = $hrdUserId;
            $result->save();

            $this->autoResolveAttendance($application, $decision, $hrdUserId);

            return $application->fresh([
                'studentAlumni.user',
                'studentAlumni.major',
                'jobVacancy',
                'selectionResult',
                'status',
            ]);
        });
    }

    public function updateDecision(int $companyId, int $applicationId, string $decision, int $hrdUserId): JobApplication
    {
        return DB::transaction(function () use ($companyId, $applicationId, $decision, $hrdUserId): JobApplication {
            $application = JobApplication::with(['jobVacancy', 'selectionResult'])
                ->where('id', $applicationId)
                ->whereHas('jobVacancy', fn (Builder $q) => $q->where('company_id', $companyId))
                ->first();

            if (! $application) {
                throw new HttpException(404, 'Data lamaran tidak ditemukan.');
            }

            $result = SelectionResult::firstOrNew(['job_application_id' => $application->id]);
            if (! $result->exists) {
                $result->created_by = $hrdUserId;
                $result->admin_selection_status = 'lolos';
            }
            $result->decision = $decision;
            $result->updated_by = $hrdUserId;
            $result->save();

            $this->autoResolveAttendance($application, $decision, $hrdUserId);

            return $application->fresh([
                'studentAlumni.user',
                'studentAlumni.major',
                'jobVacancy',
                'selectionResult',
                'status',
            ]);
        });
    }

    public function publishResults(int $companyId, int $vacancyId, int $hrdUserId): int
    {
        return DB::transaction(function () use ($companyId, $vacancyId, $hrdUserId): int {
            $vacancy = JobVacancy::where('id', $vacancyId)->where('company_id', $companyId)->first();
            if (! $vacancy) {
                throw new HttpException(404, 'Lowongan kerja tidak ditemukan.');
            }

            $applications = JobApplication::with(['selectionResult', 'studentAlumni.user'])
                ->where('job_vacancy_id', $vacancyId)
                ->get();

            $acceptedStatusId = StandardType::byCategory('job_application_status')->where('code', 'accepted')->value('id');
            $rejectedStatusId = StandardType::byCategory('job_application_status')->where('code', 'rejected')->value('id');

            $count = 0;
            foreach ($applications as $app) {
                $res = $app->selectionResult;
                if (! $res) {
                    continue;
                }

                $res->update([
                    'status' => 'published',
                    'updated_by' => $hrdUserId,
                ]);

                if ($res->decision === 'diterima' && $acceptedStatusId) {
                    $app->update(['status_id' => $acceptedStatusId, 'updated_by' => $hrdUserId]);
                    if ($app->studentAlumni?->user_id) {
                        $this->notificationService->send(
                            $app->studentAlumni->user_id,
                            'selection_accepted',
                            'Pengumuman Kelulusan: '.$vacancy->title,
                            "Selamat! Anda dinyatakan DITERIMA pada posisi {$vacancy->position} ({$vacancy->title}). Silakan unduh surat penempatan di portal siswa.",
                            ['application_id' => $app->id, 'decision' => 'diterima'],
                            true
                        );
                    }
                } elseif ($res->decision === 'tidak_diterima' && $rejectedStatusId) {
                    $app->update(['status_id' => $rejectedStatusId, 'updated_by' => $hrdUserId]);
                    if ($app->studentAlumni?->user_id) {
                        $this->notificationService->send(
                            $app->studentAlumni->user_id,
                            'selection_failed',
                            'Pengumuman Kelulusan: '.$vacancy->title,
                            "Terima kasih telah berpartisipasi pada seleksi {$vacancy->title}. Saat ini Anda belum berhasil lolos. Tetap semangat!",
                            ['application_id' => $app->id, 'decision' => 'tidak_diterima'],
                            false
                        );
                    }
                }

                $count++;
            }

            return $count;
        });
    }

    public function saveDraft(int $companyId, int $vacancyId, int $hrdUserId): int
    {
        return DB::transaction(function () use ($companyId, $vacancyId, $hrdUserId): int {
            $vacancy = JobVacancy::where('id', $vacancyId)->where('company_id', $companyId)->first();
            if (! $vacancy) {
                throw new HttpException(404, 'Lowongan kerja tidak ditemukan.');
            }

            return SelectionResult::whereHas('jobApplication', fn (Builder $q) => $q->where('job_vacancy_id', $vacancyId))
                ->update([
                    'status' => 'draft',
                    'updated_by' => $hrdUserId,
                ]);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildBaseQuery(int $companyId, array $filters): Builder
    {
        $query = JobApplication::query()
            ->whereHas('jobVacancy', function (Builder $q) use ($companyId): void {
                $q->where('company_id', $companyId);
            });

        if (! empty($filters['job_vacancy_id'])) {
            $query->where('job_applications.job_vacancy_id', (int) $filters['job_vacancy_id']);
        }

        if (! empty($filters['decision']) && $filters['decision'] !== 'semua') {
            $decision = (string) $filters['decision'];
            $query->whereHas('selectionResult', function (Builder $q) use ($decision): void {
                $q->where('decision', $decision);
            });
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->whereHas('studentAlumni', function (Builder $sa) use ($search): void {
                    $sa->where('nis', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $u) use ($search): void {
                            $u->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                })->orWhereHas('jobVacancy', function (Builder $jv) use ($search): void {
                    $jv->where('position', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            });
        }

        return $query;
    }

    private function autoResolveAttendance(JobApplication $application, string $decision, int $hrdUserId): void
    {
        $latestStageHistory = ApplicationStageHistory::with('attendance')
            ->where('job_application_id', $application->id)
            ->orderBy('id', 'desc')
            ->first();

        if (! $latestStageHistory || ! $latestStageHistory->attendance) {
            return;
        }

        $attendance = $latestStageHistory->attendance;
        if ($attendance->validation_status === 'pending') {
            if ($decision === 'tidak_diterima') {
                $absentStatusId = StandardType::byCategory('attendance_status')->where('code', 'absent')->value('id');
                $attendance->update([
                    'validation_status' => 'rejected',
                    'attendance_status_id' => $absentStatusId,
                    'validated_by' => $hrdUserId,
                    'validated_at' => now(),
                    'system_action' => 'Otomatis via Input Hasil HRD',
                ]);
            } else {
                $presentStatusId = StandardType::byCategory('attendance_status')->where('code', 'present')->value('id');
                $attendance->update([
                    'validation_status' => 'verified',
                    'attendance_status_id' => $presentStatusId,
                    'validated_by' => $hrdUserId,
                    'validated_at' => now(),
                    'system_action' => 'Otomatis via Input Hasil HRD',
                ]);
            }
        }
    }
}
