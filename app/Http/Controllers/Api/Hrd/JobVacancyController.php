<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHrdJobVacancyRequest;
use App\Http\Requests\UpdateHrdJobVacancyRequest;
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
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $filters = $request->only([
            'search',
            'status_id',
            'target_applicant_id',
            'job_type_id',
            'major_id',
            'major_ids',
            'majors',
            'is_active',
            'effective_status',
            'sort',
        ]);

        $perPage = $request->integer('per_page', 15);
        $vacancies = $this->jobVacancyService->getHrdVacancies($company->id, $filters, $perPage);

        return $this->response
            ->message('Daftar lowongan kerja berhasil diambil.')
            ->data(JobVacancyResource::collection($vacancies)->response()->getData(true));
    }

    public function store(StoreHrdJobVacancyRequest $request): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $data = $request->validated();
        $data['company_id'] = $company->id;

        $vacancy = $this->jobVacancyService->createJobVacancy(
            $data,
            $request->user()?->id
        );

        return $this->response
            ->message('Lowongan kerja berhasil ditambahkan.')
            ->data(new JobVacancyResource($vacancy))
            ->code(201);
    }

    public function show(Request $request, string $idOrSlug): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $vacancy = $this->jobVacancyService->findJobVacancyDetail($idOrSlug);

        if (! $vacancy) {
            return $this->response
                ->success(false)
                ->message('Data lowongan kerja tidak ditemukan.')
                ->code(404);
        }

        if ($vacancy->company_id !== $company->id) {
            return $this->response
                ->success(false)
                ->message('Anda tidak memiliki akses ke lowongan kerja perusahaan lain.')
                ->code(403);
        }

        return $this->response
            ->message('Detail lowongan kerja berhasil diambil.')
            ->data(new JobVacancyResource($vacancy));
    }

    public function update(UpdateHrdJobVacancyRequest $request, JobVacancy $jobVacancy): Responsable
    {
        $company = $request->user()?->company;
        if (! $company || $jobVacancy->company_id !== $company->id) {
            return $this->response
                ->success(false)
                ->message('Anda tidak memiliki akses untuk mengubah lowongan kerja ini.')
                ->code(403);
        }

        $data = $request->validated();
        unset($data['company_id']);

        $updated = $this->jobVacancyService->updateJobVacancy(
            $jobVacancy,
            $data,
            $request->user()?->id
        );

        return $this->response
            ->message('Lowongan kerja berhasil diperbarui.')
            ->data(new JobVacancyResource($updated));
    }

    public function destroy(Request $request, JobVacancy $jobVacancy): Responsable
    {
        $company = $request->user()?->company;
        if (! $company || $jobVacancy->company_id !== $company->id) {
            return $this->response
                ->success(false)
                ->message('Anda tidak memiliki akses untuk menghapus lowongan kerja ini.')
                ->code(403);
        }

        $this->jobVacancyService->deleteJobVacancy($jobVacancy, $request->user()?->id);

        return $this->response->message('Lowongan kerja berhasil dihapus.');
    }

    public function toggleActive(Request $request, JobVacancy $jobVacancy): Responsable
    {
        $company = $request->user()?->company;
        if (! $company || $jobVacancy->company_id !== $company->id) {
            return $this->response
                ->success(false)
                ->message('Anda tidak memiliki akses untuk mengubah status lowongan kerja ini.')
                ->code(403);
        }

        $updated = $this->jobVacancyService->toggleActive($jobVacancy, $request->user()?->id);
        $statusText = $updated->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->response
            ->message("Status lowongan kerja berhasil {$statusText}.")
            ->data(new JobVacancyResource($updated));
    }

    public function options(Request $request): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $options = $this->jobVacancyService->getHrdFormOptions($company);

        return $this->response
            ->message('Opsi formulir lowongan kerja berhasil diambil.')
            ->data(encrypt_recursive($options));
    }

    public function statistics(Request $request): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $stats = $this->jobVacancyService->getHrdStatistics($company->id);

        return $this->response
            ->message('Statistik lowongan kerja berhasil diambil.')
            ->data($stats);
    }
}
