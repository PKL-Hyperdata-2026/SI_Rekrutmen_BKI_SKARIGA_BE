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
        $filters = $request->only([
            'search',
            'student_alumni_id',
            'company_id',
            'placement_status_id',
            'job_application_id',
            'year',
            'sort_by',
            'sort_dir',
        ]);

        $perPage = $request->integer('per_page', 15);
        $placements = $this->jobPlacementService->index($filters, $perPage);

        return $this->response
            ->message('Daftar penempatan kerja berhasil diambil.')
            ->data(JobPlacementResource::collection($placements)->response()->getData(true));
    }

    public function options(): Responsable
    {
        $options = $this->jobPlacementService->getFormOptions();

        return $this->response
            ->message('Opsi formulir penempatan kerja berhasil diambil.')
            ->data($options);
    }

    public function show(JobPlacement $jobPlacement): Responsable
    {
        return $this->response
            ->message('Detail penempatan kerja berhasil diambil.')
            ->data(new JobPlacementResource($this->jobPlacementService->show($jobPlacement)));
    }

    public function store(StoreJobPlacementRequest $request): Responsable
    {
        $placement = $this->jobPlacementService->create($request->validated(), $request->user()?->id);

        return $this->response
            ->message('Data penempatan kerja berhasil ditambahkan.')
            ->data(new JobPlacementResource($placement))
            ->code(201);
    }

    public function update(UpdateJobPlacementRequest $request, JobPlacement $jobPlacement): Responsable
    {
        $updated = $this->jobPlacementService->update($jobPlacement, $request->validated(), $request->user()?->id);

        return $this->response
            ->message('Data penempatan kerja berhasil diperbarui.')
            ->data(new JobPlacementResource($updated));
    }

    public function destroy(Request $request, JobPlacement $jobPlacement): Responsable
    {
        $this->jobPlacementService->delete($jobPlacement, $request->user()?->id);

        return $this->response->message('Data penempatan kerja berhasil dihapus.');
    }
}
