<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobVacancyRequest;
use App\Http\Requests\UpdateJobVacancyRequest;
use App\Http\Resources\JobVacancyResource;
use App\Models\JobVacancy;
use App\Services\JobVacancyService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class JobVacancyController extends Controller
{
    public function __construct(
        protected JobVacancyService $jobVacancyService,
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $filters = $request->only([
            'search',
            'company_id',
            'status_id',
            'target_applicant_id',
            'job_type_id',
            'major_id',
            'major_ids',
            'majors',
            'is_active',
        ]);

        $perPage = $request->integer('per_page', 15);
        $vacancies = $this->jobVacancyService->getAdminVacancies($filters, $perPage);

        return $this->response
            ->message('Daftar lowongan kerja berhasil diambil.')
            ->data(JobVacancyResource::collection($vacancies)->response()->getData(true));
    }

    public function store(StoreJobVacancyRequest $request): Responsable
    {
        $vacancy = $this->jobVacancyService->createJobVacancy(
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Lowongan kerja berhasil ditambahkan.')
            ->data(new JobVacancyResource($vacancy))->code(201);
    }

    public function show(string $idOrSlug): Responsable
    {
        $vacancy = JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy'])
            ->where(function ($query) use ($idOrSlug) {
                $query->where('id', $idOrSlug)
                    ->orWhere('slug', $idOrSlug);
            })
            ->firstOrFail();

        return $this->response->message('Detail lowongan kerja berhasil diambil.')
            ->data(new JobVacancyResource($vacancy));
    }

    public function update(UpdateJobVacancyRequest $request, JobVacancy $jobVacancy): Responsable
    {
        $updated = $this->jobVacancyService->updateJobVacancy(
            $jobVacancy,
            $request->validated(),
            $request->user()?->id
        );

        return $this->response->message('Lowongan kerja berhasil diperbarui.')
            ->data(new JobVacancyResource($updated));
    }

    public function destroy(Request $request, JobVacancy $jobVacancy): Responsable
    {
        $this->jobVacancyService->deleteJobVacancy($jobVacancy, $request->user()?->id);

        return $this->response->message('Lowongan kerja berhasil dihapus.');
    }

    public function toggleActive(Request $request, JobVacancy $jobVacancy): Responsable
    {
        $updated = $this->jobVacancyService->toggleActive($jobVacancy, $request->user()?->id);
        $statusText = $updated->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return $this->response->message("Status lowongan kerja berhasil {$statusText}.")
            ->data(new JobVacancyResource($updated));
    }

    public function options(): Responsable
    {
        $options = $this->jobVacancyService->getFormOptions();
        return $this->response->message('Opsi formulir lowongan kerja berhasil diambil.')
            ->data($options);
    }
}
