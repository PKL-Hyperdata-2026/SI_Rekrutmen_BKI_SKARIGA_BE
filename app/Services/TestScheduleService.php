<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApplicationStageHistory;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionStage;
use App\Models\StandardType;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TestScheduleService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get paginated list of test schedules for a company.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getHrdTestSchedules(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SelectionStage::with(['jobVacancy'])
            ->withCount('stageHistories as total_participants')
            ->whereHas('jobVacancy', function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId);
            });

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('jobVacancy', function (Builder $jv) use ($search) {
                        jv:
                        $jv->where('title', 'like', "%{$search}%")
                            ->orWhere('position', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['job_vacancy_id'])) {
            $query->where('job_vacancy_id', $filters['job_vacancy_id']);
        }

        if (! empty($filters['session_status'])) {
            $now = now();
            if ($filters['session_status'] === 'selesai') {
                $query->where('scheduled_at', '<', $now);
            } elseif ($filters['session_status'] === 'siap' || $filters['session_status'] === 'siap_dilaksanakan') {
                $query->where(function (Builder $q) use ($now) {
                    $q->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', $now);
                });
            }
        }

        $sortBy = $filters['sort_by'] ?? 'scheduled_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['name', 'scheduled_at', 'created_at', 'minimum_score'], true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('scheduled_at', 'desc')->orderBy('id', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get single test schedule detail.
     */
    public function getHrdTestScheduleDetail(int $companyId, int $scheduleId): SelectionStage
    {
        $schedule = SelectionStage::with(['jobVacancy'])
            ->withCount('stageHistories as total_participants')
            ->where('id', $scheduleId)
            ->whereHas('jobVacancy', function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->first();

        if (! $schedule) {
            throw new HttpException(404, 'Jadwal tes tidak ditemukan.');
        }

        return $schedule;
    }

    /**
     * Create test schedule and allocate passed applicants.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTestSchedule(int $companyId, array $data, ?int $userId): SelectionStage
    {
        $vacancy = JobVacancy::where('id', $data['job_vacancy_id'])
            ->where('company_id', $companyId)
            ->first();

        if (! $vacancy) {
            throw new HttpException(403, 'Anda tidak memiliki akses ke lowongan kerja ini.');
        }

        $scheduledAt = Carbon::parse($data['scheduled_date'].' '.$data['scheduled_time']);

        return DB::transaction(function () use ($data, $vacancy, $scheduledAt, $userId) {
            $latestSequence = (int) SelectionStage::where('job_vacancy_id', $vacancy->id)->max('sequence_order');

            $stage = SelectionStage::create([
                'job_vacancy_id' => $vacancy->id,
                'stage_type_id' => null,
                'name' => $data['name'],
                'sequence_order' => $latestSequence + 1,
                'description' => $data['description'] ?? null,
                'minimum_score' => $data['minimum_score'] ?? null,
                'scheduled_at' => $scheduledAt,
                'location' => $data['location'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Query only applicants of this vacancy who passed the review (Lolos Berkas)
            $applicantsQuery = JobApplication::where('job_vacancy_id', $vacancy->id)
                ->where(function (Builder $q) {
                    $q->whereHas('selectionResult', function (Builder $sr) {
                        $sr->where('admin_selection_status', 'lolos');
                    })
                        ->orWhereHas('status', function (Builder $s) {
                            $s->whereIn('code', ['in_progress', 'accepted']);
                        });
                })
                ->with(['studentAlumni.user']);

            if (! empty($data['application_ids']) && is_array($data['application_ids'])) {
                $applicantsQuery->whereIn('id', $data['application_ids']);
            }

            $eligibleApplicants = $applicantsQuery->get();

            $scheduledStatus = StandardType::byCategory('application_stage_status')
                ->where('code', 'scheduled')
                ->first();

            $sendNotification = ! empty($data['send_notification']);

            foreach ($eligibleApplicants as $applicant) {
                $stageHistory = ApplicationStageHistory::create([
                    'job_application_id' => $applicant->id,
                    'selection_stage_id' => $stage->id,
                    'status_id' => $scheduledStatus?->id,
                    'assessor_id' => $userId,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                RecruitmentAttendance::create([
                    'stage_history_id' => $stageHistory->id,
                    'attendance_status_id' => null,
                ]);

                $applicant->update([
                    'current_stage_id' => $stage->id,
                    'updated_by' => $userId,
                ]);

                if ($sendNotification && $applicant->studentAlumni?->user_id) {
                    $recipientUserId = $applicant->studentAlumni->user_id;
                    $formattedDate = $scheduledAt->translatedFormat('d M Y • H:i').' WIB';
                    $this->notificationService->send(
                        $recipientUserId,
                        'test_schedule',
                        'Jadwal Tes Baru: '.$stage->name,
                        "Anda telah dijadwalkan untuk mengikuti agenda tes {$stage->name} untuk posisi {$vacancy->position} pada {$formattedDate} di {$stage->location}.",
                        [
                            'schedule_id' => $stage->id,
                            'job_vacancy_id' => $vacancy->id,
                            'stage_name' => $stage->name,
                            'scheduled_at' => $scheduledAt->toIso8601String(),
                            'location' => $stage->location,
                        ],
                        false
                    );
                }
            }

            $stage->load(['jobVacancy']);
            $stage->total_participants = $eligibleApplicants->count();

            return $stage;
        });
    }

    /**
     * Update test schedule.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTestSchedule(SelectionStage $schedule, array $data, ?int $userId): SelectionStage
    {
        return DB::transaction(function () use ($schedule, $data, $userId) {
            $updateData = [
                'updated_by' => $userId,
            ];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['minimum_score'])) {
                $updateData['minimum_score'] = $data['minimum_score'];
            }
            if (isset($data['location'])) {
                $updateData['location'] = $data['location'];
            }

            if (! empty($data['scheduled_date']) && ! empty($data['scheduled_time'])) {
                $updateData['scheduled_at'] = Carbon::parse($data['scheduled_date'].' '.$data['scheduled_time']);
            } elseif (! empty($data['scheduled_date']) && $schedule->scheduled_at) {
                $time = $schedule->scheduled_at->format('H:i');
                $updateData['scheduled_at'] = Carbon::parse($data['scheduled_date'].' '.$time);
            }

            $schedule->update($updateData);

            if (! empty($data['send_notification'])) {
                $stageHistories = ApplicationStageHistory::where('selection_stage_id', $schedule->id)
                    ->with(['jobApplication.studentAlumni.user'])
                    ->get();

                $vacancy = $schedule->jobVacancy;
                $formattedDate = $schedule->scheduled_at?->translatedFormat('d M Y • H:i').' WIB';

                foreach ($stageHistories as $history) {
                    $studentUser = $history->jobApplication?->studentAlumni?->user;
                    if ($studentUser) {
                        $this->notificationService->send(
                            $studentUser->id,
                            'test_schedule_update',
                            'Perubahan Jadwal Tes: '.$schedule->name,
                            "Terdapat perubahan jadwal/lokasi untuk agenda tes {$schedule->name} posisi {$vacancy?->position}. Waktu baru: {$formattedDate} di {$schedule->location}.",
                            [
                                'schedule_id' => $schedule->id,
                                'scheduled_at' => $schedule->scheduled_at?->toIso8601String(),
                                'location' => $schedule->location,
                            ],
                            false
                        );
                    }
                }
            }

            $schedule->load(['jobVacancy']);
            $schedule->loadCount('stageHistories as total_participants');

            return $schedule;
        });
    }

    /**
     * Soft delete test schedule.
     */
    public function deleteTestSchedule(SelectionStage $schedule, ?int $userId): bool
    {
        return DB::transaction(function () use ($schedule, $userId) {
            $schedule->update(['deleted_by' => $userId]);

            return (bool) $schedule->delete();
        });
    }

    /**
     * Get paginated participants for a test schedule.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getScheduleParticipants(int $companyId, int $scheduleId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->getHrdTestScheduleDetail($companyId, $scheduleId);

        $query = ApplicationStageHistory::where('selection_stage_id', $scheduleId)
            ->with([
                'jobApplication.studentAlumni.user',
                'attendance.attendanceStatus',
                'status',
            ]);

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->whereHas('jobApplication.studentAlumni', function (Builder $sa) use ($search) {
                $sa->where('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $u) use ($search) {
                        $u->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Send reminder to a specific test participant.
     */
    public function remindParticipant(int $companyId, int $scheduleId, int $historyId, ?int $userId): bool
    {
        $schedule = $this->getHrdTestScheduleDetail($companyId, $scheduleId);

        $history = ApplicationStageHistory::where('id', $historyId)
            ->where('selection_stage_id', $scheduleId)
            ->with(['jobApplication.studentAlumni.user'])
            ->first();

        if (! $history) {
            throw new HttpException(404, 'Peserta tes tidak ditemukan pada agenda ini.');
        }

        $studentUser = $history->jobApplication?->studentAlumni?->user;
        if (! $studentUser) {
            throw new HttpException(422, 'Data akun peserta tidak ditemukan.');
        }

        $formattedDate = $schedule->scheduled_at?->translatedFormat('d M Y • H:i').' WIB';
        $vacancyPosition = $schedule->jobVacancy?->position ?? 'lowongan pekerjaan';

        return $this->notificationService->send(
            $studentUser->id,
            'test_reminder',
            'Pengingat Jadwal Tes: '.$schedule->name,
            "Halo {$studentUser->full_name}, jangan lupa agenda tes {$schedule->name} untuk posisi {$vacancyPosition} akan diselenggarakan pada {$formattedDate} di {$schedule->location}. Mohon hadir tepat waktu.",
            [
                'schedule_id' => $schedule->id,
                'stage_history_id' => $history->id,
                'scheduled_at' => $schedule->scheduled_at?->toIso8601String(),
                'location' => $schedule->location,
            ],
            true
        );
    }

    /**
     * Get form options for HRD test schedules (company's active vacancies).
     *
     * @return array<string, mixed>
     */
    public function getHrdFormOptions(int $companyId): array
    {
        $vacancies = JobVacancy::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'title', 'position', 'quota')
            ->orderBy('title')
            ->get();

        return [
            'vacancies' => encrypt_recursive($vacancies),
        ];
    }
}
