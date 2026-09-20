<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobVacancy;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionStage;
use App\Models\StandardType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RecruitmentAttendanceService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function getPendingQueue(array $filters = []): LengthAwarePaginator
    {
        $query = RecruitmentAttendance::query()
            ->where('validation_status', 'pending')
            ->with([
                'stageHistory.jobApplication.studentAlumni.user',
                'stageHistory.jobApplication.studentAlumni.major',
                'stageHistory.jobApplication.jobVacancy.company',
                'stageHistory.selectionStage',
                'validator',
            ]);

        $this->applyFilters($query, $filters);

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 10;

        return $query->orderBy('attended_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getHistory(array $filters = []): LengthAwarePaginator
    {
        $query = RecruitmentAttendance::query()
            ->whereIn('validation_status', ['verified', 'rejected'])
            ->with([
                'stageHistory.jobApplication.studentAlumni.user',
                'stageHistory.jobApplication.studentAlumni.major',
                'stageHistory.jobApplication.jobVacancy.company',
                'stageHistory.selectionStage',
                'validator',
            ]);

        $this->applyFilters($query, $filters);

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 10;

        return $query->orderBy('validated_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getStageSummaries(?int $jobVacancyId = null): array
    {
        $totalPendingQuery = RecruitmentAttendance::query()->where('validation_status', 'pending');
        if ($jobVacancyId) {
            $totalPendingQuery->whereHas('stageHistory.jobApplication', function (Builder $q) use ($jobVacancyId) {
                $q->where('job_vacancy_id', $jobVacancyId);
            });
        }
        $totalPendingCount = $totalPendingQuery->count();

        $summaries = [
            [
                'id' => null,
                'name' => 'Semua Kategori',
                'sequence_order' => 0,
                'participant_count' => $totalPendingCount,
                'stage_ids' => [],
            ],
        ];

        $stagesQuery = SelectionStage::query()->orderBy('sequence_order', 'asc');
        if ($jobVacancyId) {
            $stagesQuery->where('job_vacancy_id', $jobVacancyId);
        }

        $stages = $stagesQuery->get();

        $groups = [];
        foreach ($stages as $stage) {
            $key = strtolower(trim((string) $stage->name));
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'id' => $stage->id,
                    'name' => $stage->name !== '' ? $stage->name : 'Tahap '.$stage->sequence_order,
                    'sequence_order' => $stage->sequence_order,
                    'stage_ids' => [],
                ];
            }
            $groups[$key]['stage_ids'][] = $stage->id;
            if ($stage->sequence_order < $groups[$key]['sequence_order']) {
                $groups[$key]['sequence_order'] = $stage->sequence_order;
            }
        }

        $groupedStageIds = [];
        foreach ($groups as $group) {
            foreach ($group['stage_ids'] as $stageId) {
                $groupedStageIds[] = $stageId;
            }
        }

        $counts = [];
        if ($groupedStageIds !== []) {
            $counts = DB::table('recruitment_attendances as ra')
                ->join('application_stage_histories as ash', 'ash.id', '=', 'ra.stage_history_id')
                ->where('ra.validation_status', 'pending')
                ->whereIn('ash.selection_stage_id', $groupedStageIds)
                ->groupBy('ash.selection_stage_id')
                ->pluck(DB::raw('count(*) as aggregate'), 'ash.selection_stage_id')
                ->map(fn ($value) => (int) $value)
                ->all();
        }

        foreach ($groups as $group) {
            $participantCount = 0;
            foreach ($group['stage_ids'] as $stageId) {
                $participantCount += $counts[$stageId] ?? 0;
            }

            $summaries[] = [
                'id' => encrypt((string) $group['id']),
                'name' => $group['name'],
                'sequence_order' => $group['sequence_order'],
                'participant_count' => $participantCount,
                'stage_ids' => array_map(fn ($stageId) => encrypt((string) $stageId), array_values($group['stage_ids'])),
            ];
        }

        return $summaries;
    }

    public function getVacancyFilterOptions(): array
    {
        return JobVacancy::query()
            ->with('company')
            ->whereHas('jobApplications.stageHistories.attendance')
            ->get()
            ->sortBy([
                fn (JobVacancy $a, JobVacancy $b): int => strcasecmp($a->company?->name ?? '', $b->company?->name ?? ''),
                fn (JobVacancy $a, JobVacancy $b): int => strcasecmp($a->title, $b->title),
            ])
            ->map(function (JobVacancy $vacancy) {
                $rawTitle = trim($vacancy->title);
                $lowerTitle = strtolower($rawTitle);
                if (str_starts_with($lowerTitle, 'lowongan kerja ')) {
                    $cleanTitle = trim(substr($rawTitle, 15));
                } elseif (str_starts_with($lowerTitle, 'lowongan ')) {
                    $cleanTitle = trim(substr($rawTitle, 9));
                } else {
                    $cleanTitle = $rawTitle;
                }

                return [
                    'id' => encrypt((string) $vacancy->id),
                    'title' => $cleanTitle,
                    'company_name' => $vacancy->company?->name,
                    'label' => ($vacancy->company?->name ? $vacancy->company->name.' - ' : '').$cleanTitle,
                ];
            })
            ->values()
            ->all();
    }

    public function validateAttendance(RecruitmentAttendance $attendance, int $adminId, array $data): RecruitmentAttendance
    {
        if ($attendance->validation_status !== 'pending') {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Presensi ini sudah divalidasi sebelumnya.',
                'data' => null,
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return DB::transaction(function () use ($attendance, $adminId, $data) {
            $status = $data['validation_status'];
            $isVerified = $status === 'verified';

            $attendanceStatusCode = $isVerified ? 'present' : 'absent';
            $attendanceStatus = StandardType::byCategory('attendance_status')
                ->where('code', $attendanceStatusCode)
                ->first();

            $defaultAction = $isVerified
                ? 'Diteruskan ke HRD'
                : 'Gugur / Tidak Hadir';

            $attendance->update([
                'validation_status' => $status,
                'attendance_status_id' => $attendanceStatus?->id ?? $attendance->attendance_status_id,
                'validated_by' => $adminId,
                'validated_at' => now(),
                'notes' => $data['notes'] ?? null,
                'system_action' => $data['system_action'] ?? $defaultAction,
            ]);

            if (! $isVerified && $attendance->stageHistory) {
                $absentStageStatus = StandardType::byCategory('application_stage_status')
                    ->where('code', 'absent')
                    ->first();

                if ($absentStageStatus) {
                    $attendance->stageHistory->update([
                        'status_id' => $absentStageStatus->id,
                        'notes' => $data['notes'] ?? 'Tidak hadir dalam presensi tahapan seleksi.',
                        'updated_by' => $adminId,
                    ]);
                }
            }

            $validated = $attendance->fresh([
                'stageHistory.jobApplication.studentAlumni.user',
                'stageHistory.jobApplication.studentAlumni.major',
                'stageHistory.jobApplication.jobVacancy.company',
                'stageHistory.selectionStage',
                'validator',
            ]);

            $studentUser = $validated->stageHistory?->jobApplication?->studentAlumni?->user;
            if ($studentUser) {
                $stageName = $validated->stageHistory?->selectionStage?->name ?? 'Tahap Seleksi';
                $vacancyTitle = $validated->stageHistory?->jobApplication?->jobVacancy?->title
                    ?: ($validated->stageHistory?->jobApplication?->jobVacancy?->position ?? 'Lowongan');

                if ($isVerified) {
                    $this->notificationService->send(
                        $studentUser->id,
                        'attendance_validated',
                        'Presensi Terverifikasi: '.$stageName,
                        "Presensi kehadiran Anda untuk {$stageName} pada lowongan {$vacancyTitle} telah diverifikasi oleh panitia.",
                        [
                            'attendance_id' => $validated->id,
                            'status' => 'verified',
                            'stage_name' => $stageName,
                        ],
                        true
                    );
                } else {
                    $notes = $data['notes'] ?? null;
                    $this->notificationService->send(
                        $studentUser->id,
                        'attendance_rejected',
                        'Presensi Ditolak: '.$stageName,
                        "Presensi kehadiran Anda untuk {$stageName} pada lowongan {$vacancyTitle} ditolak.".($notes ? " Catatan: {$notes}" : ''),
                        [
                            'attendance_id' => $validated->id,
                            'status' => 'rejected',
                            'stage_name' => $stageName,
                        ],
                        true
                    );
                }
            }

            return $validated;
        });
    }

    public function bulkValidateAttendance(array $attendanceIds, int $adminId, array $data): int
    {
        return DB::transaction(function () use ($attendanceIds, $adminId, $data) {
            $status = $data['validation_status'];
            $isVerified = $status === 'verified';

            $attendanceStatusCode = $isVerified ? 'present' : 'absent';
            $attendanceStatus = StandardType::byCategory('attendance_status')
                ->where('code', $attendanceStatusCode)
                ->first();

            $defaultAction = $isVerified
                ? 'Diteruskan ke HRD'
                : 'Gugur / Tidak Hadir';

            $attendances = RecruitmentAttendance::query()
                ->whereIn('id', $attendanceIds)
                ->with([
                    'stageHistory.jobApplication.studentAlumni.user',
                    'stageHistory.jobApplication.jobVacancy',
                    'stageHistory.selectionStage',
                ])
                ->get();

            $absentStageStatus = null;
            if (! $isVerified) {
                $absentStageStatus = StandardType::byCategory('application_stage_status')
                    ->where('code', 'absent')
                    ->first();
            }

            $count = 0;
            foreach ($attendances as $attendance) {
                $attendance->update([
                    'validation_status' => $status,
                    'attendance_status_id' => $attendanceStatus?->id ?? $attendance->attendance_status_id,
                    'validated_by' => $adminId,
                    'validated_at' => now(),
                    'notes' => $data['notes'] ?? null,
                    'system_action' => $data['system_action'] ?? $defaultAction,
                ]);

                if (! $isVerified && $attendance->stageHistory && $absentStageStatus) {
                    $attendance->stageHistory->update([
                        'status_id' => $absentStageStatus->id,
                        'notes' => $data['notes'] ?? 'Tidak hadir dalam presensi tahapan seleksi.',
                        'updated_by' => $adminId,
                    ]);
                }

                $studentUser = $attendance->stageHistory?->jobApplication?->studentAlumni?->user;
                if ($studentUser) {
                    $stageName = $attendance->stageHistory?->selectionStage?->name ?? 'Tahap Seleksi';
                    $vacancyTitle = $attendance->stageHistory?->jobApplication?->jobVacancy?->title
                        ?: ($attendance->stageHistory?->jobApplication?->jobVacancy?->position ?? 'Lowongan');

                    if ($isVerified) {
                        $this->notificationService->send(
                            $studentUser->id,
                            'attendance_validated',
                            'Presensi Terverifikasi: '.$stageName,
                            "Presensi kehadiran Anda untuk {$stageName} pada lowongan {$vacancyTitle} telah diverifikasi oleh panitia.",
                            [
                                'attendance_id' => $attendance->id,
                                'status' => 'verified',
                                'stage_name' => $stageName,
                            ],
                            true
                        );
                    } else {
                        $notes = $data['notes'] ?? null;
                        $this->notificationService->send(
                            $studentUser->id,
                            'attendance_rejected',
                            'Presensi Ditolak: '.$stageName,
                            "Presensi kehadiran Anda untuk {$stageName} pada lowongan {$vacancyTitle} ditolak.".($notes ? " Catatan: {$notes}" : ''),
                            [
                                'attendance_id' => $attendance->id,
                                'status' => 'rejected',
                                'stage_name' => $stageName,
                            ],
                            true
                        );
                    }
                }

                $count++;
            }

            return $count;
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['job_vacancy_id'])) {
            $vacancyId = (int) $filters['job_vacancy_id'];
            $query->whereHas('stageHistory.jobApplication', function (Builder $q) use ($vacancyId) {
                $q->where('job_vacancy_id', $vacancyId);
            });
        }

        if (! empty($filters['stage_ids']) && is_array($filters['stage_ids'])) {
            $stageIds = array_map('intval', $filters['stage_ids']);
            $query->whereHas('stageHistory', function (Builder $q) use ($stageIds) {
                $q->whereIn('selection_stage_id', $stageIds);
            });
        } elseif (! empty($filters['stage_id'])) {
            $stageId = (int) $filters['stage_id'];
            $query->whereHas('stageHistory', function (Builder $q) use ($stageId) {
                $q->where('selection_stage_id', $stageId);
            });
        }

        if (! empty($filters['validation_status'])) {
            $query->where('validation_status', $filters['validation_status']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $sub) use ($search) {
                $sub->whereHas('stageHistory.jobApplication.studentAlumni.user', function (Builder $q) use ($search) {
                    $q->where('full_name', 'ilike', "%{$search}%");
                })->orWhereHas('stageHistory.jobApplication.studentAlumni', function (Builder $q) use ($search) {
                    $q->where('nis', 'like', "%{$search}%");
                })->orWhereHas('stageHistory.jobApplication.jobVacancy', function (Builder $q) use ($search) {
                    $q->where('title', 'ilike', "%{$search}%");
                });
            });
        }
    }
}
