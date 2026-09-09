<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobPlacementRequest;
use App\Http\Requests\UpdateJobPlacementRequest;
use App\Http\Resources\JobPlacementResource;
use App\Models\JobPlacement;
use App\Services\JobPlacementService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class JobPlacementController extends Controller
{
    public function __construct(
        protected JobPlacementService $jobPlacementService,
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $companyId = $this->jobPlacementService->getCompanyIdByUserId($request->user()?->id);
        if (! $companyId) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $filters = $request->only([
            'search',
            'student_alumni_id',
            'placement_status_id',
            'job_application_id',
            'year',
            'sort_by',
            'sort_dir',
        ]);
        $filters['company_id'] = $companyId;

        $perPage = $request->integer('per_page', 15);
        $placements = $this->jobPlacementService->index($filters, $perPage);

        return $this->response
            ->message('Daftar penempatan kerja berhasil diambil.')
            ->data(JobPlacementResource::collection($placements)->response()->getData(true));
    }

    public function metrics(Request $request): Responsable
    {
        $companyId = $this->jobPlacementService->getCompanyIdByUserId($request->user()?->id);
        if (! $companyId) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $filters = $request->only(['year']);
        $filters['company_id'] = $companyId;

        $metrics = $this->jobPlacementService->getMetrics($filters);

        return $this->response
            ->message('Metrik penempatan kerja berhasil diambil.')
            ->data($metrics);
    }

    public function options(Request $request): Responsable
    {
        $companyId = $this->jobPlacementService->getCompanyIdByUserId($request->user()?->id);
        $options = $this->jobPlacementService->getFormOptions($companyId);

        return $this->response
            ->message('Opsi formulir penempatan kerja berhasil diambil.')
            ->data(encrypt_recursive($options));
    }

    public function show(Request $request, JobPlacement $jobPlacement): Responsable
    {
        $companyId = $this->jobPlacementService->getCompanyIdByUserId($request->user()?->id);
        if (! $companyId || ! $this->jobPlacementService->belongsToCompany($jobPlacement, $companyId)) {
            return $this->response
                ->success(false)
                ->message('Anda tidak memiliki akses ke data penempatan kerja ini.')
                ->code(403);
        }

        return $this->response
            ->message('Detail penempatan kerja berhasil diambil.')
            ->data(new JobPlacementResource($this->jobPlacementService->show($jobPlacement)));
    }

    public function store(StoreJobPlacementRequest $request): Responsable
    {
        $companyId = $this->jobPlacementService->getCompanyIdByUserId($request->user()?->id);
        if (! $companyId) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $data = $request->validated();
        $data['company_id'] = $companyId;

        $placement = $this->jobPlacementService->create($data, $request->user()?->id);

        return $this->response
            ->message('Data penempatan kerja berhasil ditambahkan.')
            ->data(new JobPlacementResource($placement))
            ->code(201);
    }

    public function update(UpdateJobPlacementRequest $request, JobPlacement $jobPlacement): Responsable
    {
        $companyId = $this->jobPlacementService->getCompanyIdByUserId($request->user()?->id);
        if (! $companyId || ! $this->jobPlacementService->belongsToCompany($jobPlacement, $companyId)) {
            return $this->response
                ->success(false)
                ->message('Anda tidak memiliki hak untuk mengubah data penempatan kerja ini.')
                ->code(403);
        }

        $data = $request->validated();
        $data['company_id'] = $companyId;

        $updated = $this->jobPlacementService->update($jobPlacement, $data, $request->user()?->id);

        return $this->response
            ->message('Data penempatan kerja berhasil diperbarui.')
            ->data(new JobPlacementResource($updated));
    }

    public function destroy(Request $request, JobPlacement $jobPlacement): Responsable
    {
        $companyId = $this->jobPlacementService->getCompanyIdByUserId($request->user()?->id);
        if (! $companyId || ! $this->jobPlacementService->belongsToCompany($jobPlacement, $companyId)) {
            return $this->response
                ->success(false)
                ->message('Anda tidak memiliki hak untuk menghapus data penempatan kerja ini.')
                ->code(403);
        }

        $this->jobPlacementService->delete($jobPlacement, $request->user()?->id);

        return $this->response->message('Data penempatan kerja berhasil dihapus.');
    }
}
