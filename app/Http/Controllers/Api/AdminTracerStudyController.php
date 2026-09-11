<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminTracerStudyRequest;
use App\Http\Requests\UpdateAdminTracerStudyRequest;
use App\Http\Resources\TracerStudyResource;
use App\Models\TracerStudy;
use App\Services\ResponseService;
use App\Services\TracerStudyService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class AdminTracerStudyController extends Controller
{
    public function __construct(
        protected TracerStudyService $tracerStudyService,
        protected ResponseService $response
    ) {}

    /**
     * List tracer study untuk Admin (search, filter, sort, pagination 10 per page).
     */
    public function index(Request $request): Responsable
    {
        $filters = $request->only([
            'search',
            'career_status',
            'major_id',
            'graduation_year',
            'sort_by',
            'sort_dir',
        ]);

        $perPage = $request->integer('per_page', 10);
        $paginator = $this->tracerStudyService->indexAdmin($filters, $perPage);

        return $this->response
            ->message('Daftar tracer study berhasil diambil.')
            ->data(TracerStudyResource::collection($paginator)->response()->getData(true));
    }

    /**
     * Ambil data metrik agregat 5 Card Tracer Study.
     */
    public function metrics(): Responsable
    {
        $metrics = $this->tracerStudyService->getMetrics();

        return $this->response
            ->message('Metrik tracer study berhasil diambil.')
            ->data($metrics);
    }

    /**
     * Ambil data opsi filter & form Tracer Study.
     */
    public function options(): Responsable
    {
        $options = $this->tracerStudyService->getAdminFormOptions();

        return $this->response
            ->message('Opsi formulir tracer study berhasil diambil.')
            ->data($options);
    }

    /**
     * Detail data tracer study alumni.
     */
    public function show(TracerStudy $tracerStudy): Responsable
    {
        $tracerStudy->load([
            'studentAlumni.user',
            'studentAlumni.major',
            'studentAlumni.class',
            'studentAlumni.jobPlacements.company',
            'studentAlumni.jobPlacements.placementStatus',
        ]);

        return $this->response
            ->message('Detail tracer study berhasil diambil.')
            ->data(new TracerStudyResource($tracerStudy));
    }

    /**
     * Admin menambahkan data tracer study alumni.
     */
    public function store(StoreAdminTracerStudyRequest $request): Responsable
    {
        $tracer = $this->tracerStudyService->createAdmin(
            $request->validated(),
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Data tracer study berhasil ditambahkan.')
            ->data(new TracerStudyResource($tracer))
            ->code(201);
    }

    /**
     * Admin memperbarui data tracer study alumni.
     */
    public function update(UpdateAdminTracerStudyRequest $request, TracerStudy $tracerStudy): Responsable
    {
        $updated = $this->tracerStudyService->updateAdmin(
            $tracerStudy,
            $request->validated(),
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Data tracer study berhasil diperbarui.')
            ->data(new TracerStudyResource($updated));
    }

    /**
     * Admin menghapus (soft delete) data tracer study alumni.
     */
    public function destroy(Request $request, TracerStudy $tracerStudy): Responsable
    {
        $this->tracerStudyService->deleteAdmin($tracerStudy, (int) $request->user()?->id);

        return $this->response
            ->message('Data tracer study berhasil dihapus.');
    }

    /**
     * Sinkronisasi data tracer study dari data penempatan & profil alumni.
     */
    public function sync(Request $request): Responsable
    {
        $count = $this->tracerStudyService->syncFromPlacements((int) $request->user()?->id);

        return $this->response
            ->message("Berhasil menyinkronkan {$count} data tracer study alumni.")
            ->data(['synced_count' => $count]);
    }
}
