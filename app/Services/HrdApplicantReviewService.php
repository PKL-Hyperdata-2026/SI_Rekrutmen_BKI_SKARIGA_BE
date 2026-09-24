<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApplicationStageHistory;
use App\Models\JobApplication;
use App\Models\JobPlacement;
use App\Models\JobVacancy;
use App\Models\SelectionResult;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HrdApplicantReviewService
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
            'studentAlumni.class',
            'studentAlumni.portfolios.category',
            'jobVacancy',
            'status',
            'selectionResult',
        ]);

        $sortBy = (string) ($filters['sort_by'] ?? 'applied_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->select('job_applications.*');

        if ($sortBy === 'name') {
            $query->leftJoin('students_alumni as sa_sort', 'sa_sort.id', '=', 'job_applications.student_alumni_id');
            $query->leftJoin('users as u_sort', 'u_sort.id', '=', 'sa_sort.user_id');
            $query->orderBy('u_sort.full_name', $sortDir)->orderBy('job_applications.id', $sortDir);
        } elseif ($sortBy === 'position') {
            $query->leftJoin('job_vacancies as jv_sort', 'jv_sort.id', '=', 'job_applications.job_vacancy_id');
            $query->orderBy('jv_sort.position', $sortDir)->orderBy('job_applications.id', $sortDir);
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
        unset($baseFilters['review_status']);

        $base = $this->buildBaseQuery($companyId, $baseFilters);
        $total = (clone $base)->count();

        $lolos = (clone $base)->whereHas('selectionResult', function (Builder $q): void {
            $q->where('admin_selection_status', 'lolos');
        })->count();

        $ditolak = (clone $base)->whereHas('selectionResult', function (Builder $q): void {
            $q->where('admin_selection_status', 'tidak_lolos');
        })->count();

        return [
            'total' => $total,
            'perlu_review' => max(0, $total - $lolos - $ditolak),
            'lolos_berkas' => $lolos,
            'ditolak' => $ditolak,
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
            ->map(fn (JobVacancy $vacancy): array => [
                'value' => $vacancy->id,
                'label' => $vacancy->position !== '' ? (string) $vacancy->position : (string) $vacancy->title,
                'extra' => ['title' => $vacancy->title],
            ])
            ->values()
            ->all();

        return [
            'vacancies' => $vacancies,
            'review_statuses' => [
                ['value' => 'semua', 'label' => 'Semua Status'],
                ['value' => 'perlu_review', 'label' => 'Menunggu Review'],
                ['value' => 'lolos_berkas', 'label' => 'Lolos Berkas'],
                ['value' => 'ditolak', 'label' => 'Ditolak'],
            ],
        ];
    }

    public function getDetail(int $companyId, int $applicationId): JobApplication
    {
        $application = JobApplication::where('id', $applicationId)
            ->whereHas('jobVacancy', function (Builder $q) use ($companyId): void {
                $q->where('company_id', $companyId);
            })
            ->with([
                'studentAlumni.user',
                'studentAlumni.major',
                'studentAlumni.class',
                'studentAlumni.portfolios.category',
                'jobVacancy.company',
                'status',
                'currentStage',
                'selectionResult',
                'stageHistories.selectionStage',
                'stageHistories.status',
                'stageHistories.assessor',
            ])
            ->first();

        if (! $application) {
            throw new HttpException(404, 'Data lamaran tidak ditemukan.');
        }

        return $application;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function review(int $companyId, int $applicationId, array $data, ?int $hrdUserId): JobApplication
    {
        return DB::transaction(function () use ($companyId, $applicationId, $data, $hrdUserId): JobApplication {
            $application = $this->getDetail($companyId, $applicationId);
            $this->assertReviewable($application);
            $this->applyDecision($application, (string) $data['decision'], $data['notes'] ?? null, $hrdUserId);

            return $this->getDetail($companyId, $applicationId);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function bulkReview(int $companyId, array $data, ?int $hrdUserId): array
    {
        return DB::transaction(function () use ($companyId, $data, $hrdUserId): array {
            $ids = array_values(array_unique(array_map('intval', (array) $data['application_ids'])));
            $decision = (string) $data['decision'];
            $notes = $data['notes'] ?? null;
            $succeeded = 0;
            $failures = [];

            foreach ($ids as $id) {
                try {
                    $application = $this->getDetail($companyId, $id);
                    $this->assertReviewable($application);
                    $this->applyDecision($application, $decision, $notes, $hrdUserId);
                    $succeeded++;
                } catch (\Throwable $exception) {
                    $failures[] = ['id' => $id, 'message' => $exception->getMessage()];
                }
            }

            return [
                'processed' => count($ids),
                'succeeded' => $succeeded,
                'failed' => count($failures),
                'failures' => $failures,
            ];
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

        if (! empty($filters['review_status']) && $filters['review_status'] !== 'semua') {
            $status = (string) $filters['review_status'];

            if ($status === 'lolos_berkas') {
                $query->whereHas('selectionResult', function (Builder $q): void {
                    $q->where('admin_selection_status', 'lolos');
                });
            } elseif ($status === 'ditolak') {
                $query->whereHas('selectionResult', function (Builder $q): void {
                    $q->where('admin_selection_status', 'tidak_lolos');
                });
            } elseif ($status === 'perlu_review') {
                $query->where(function (Builder $q): void {
                    $q->whereDoesntHave('selectionResult')->orWhereHas('selectionResult', function (Builder $sr): void {
                        $sr->whereNull('admin_selection_status');
                    });
                });
            }
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->whereHas('studentAlumni', function (Builder $sa) use ($search): void {
                    $sa->where('nis', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $u) use ($search): void {
                            $u->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                })->orWhereHas('jobVacancy', function (Builder $jv) use ($search): void {
                    $jv->where('position', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            });
        }

        return $query;
    }

    private function resolveAdminStage(JobApplication $application, ?int $hrdUserId): SelectionStage
    {
        $stage = SelectionStage::where('job_vacancy_id', $application->job_vacancy_id)
            ->orderBy('sequence_order', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if (! $stage) {
            $stage = SelectionStage::create([
                'job_vacancy_id' => $application->job_vacancy_id,
                'stage_type_id' => null,
                'name' => 'Tahap 1 - Administrasi Berkas',
                'sequence_order' => 1,
                'description' => 'Verifikasi kelengkapan berkas pelamar.',
                'location' => null,
                'created_by' => $hrdUserId,
                'updated_by' => $hrdUserId,
            ]);
        }

        return $stage;
    }

    private function assertReviewable(JobApplication $application): void
    {
        if ($application->status?->code === 'accepted') {
            throw new HttpException(422, 'Lamaran sudah diterima dan tidak dapat direview ulang.');
        }

        $placed = JobPlacement::where('job_application_id', $application->id)->exists();
        if ($placed) {
            throw new HttpException(422, 'Lamaran sudah masuk penempatan kerja.');
        }
    }

    private function applyDecision(JobApplication $application, string $decision, ?string $notes, ?int $hrdUserId): void
    {
        static $stageStatuses = [];
        static $applicationStatuses = [];

        $isLolos = $decision === 'lolos';
        $stage = $this->resolveAdminStage($application, $hrdUserId);

        $result = SelectionResult::firstOrNew(['job_application_id' => $application->id]);
        if (! $result->exists) {
            $result->created_by = $hrdUserId;
        }
        $result->fill([
            'admin_selection_status' => $isLolos ? 'lolos' : 'tidak_lolos',
            'notes' => $notes,
            'updated_by' => $hrdUserId,
        ])->save();

        $stageStatusCode = $isLolos ? 'passed' : 'failed';
        $stageStatusId = $stageStatuses[$stageStatusCode] ??= StandardType::byCategory('application_stage_status')
            ->where('code', $stageStatusCode)
            ->value('id');

        ApplicationStageHistory::updateOrCreate(
            [
                'job_application_id' => $application->id,
                'selection_stage_id' => $stage->id,
            ],
            [
                'status_id' => $stageStatusId,
                'assessor_id' => $hrdUserId,
                'notes' => $notes,
                'updated_by' => $hrdUserId,
            ]
        );

        $appStatusCode = $isLolos ? 'in_progress' : 'rejected';
        $applicationStatusId = $applicationStatuses[$appStatusCode] ??= StandardType::byCategory('job_application_status')
            ->where('code', $appStatusCode)
            ->value('id');

        $application->update([
            'status_id' => $applicationStatusId,
            'current_stage_id' => $stage->id,
            'updated_by' => $hrdUserId,
        ]);

        if (! $isLolos) {
            $studentUser = $application->studentAlumni?->user;
            if ($studentUser) {
                $vacancy = $application->jobVacancy;
                $position = $vacancy?->position ?? 'Lowongan Kerja';
                $companyName = $vacancy?->company?->name ?? 'Perusahaan Mitra';
                $title = "Hasil Seleksi Administrasi: {$position}";
                $message = "Terima kasih atas partisipasi Anda pada seleksi {$position} di {$companyName}. Saat ini berkas Anda belum lolos ke tahap berikutnya. Tetap semangat!";

                $this->notificationService->send(
                    $studentUser->id,
                    'admin_review_failed',
                    $title,
                    $message,
                    [
                        'application_id' => $application->id,
                        'job_vacancy_id' => $application->job_vacancy_id,
                        'position' => $position,
                        'company_name' => $companyName,
                        'notes' => $notes,
                    ],
                    false
                );
            }
        }
    }
}
