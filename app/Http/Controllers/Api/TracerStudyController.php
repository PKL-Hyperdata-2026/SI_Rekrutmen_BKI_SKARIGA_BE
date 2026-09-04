<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTracerStudyRequest;
use App\Http\Resources\TracerStudyResource;
use App\Services\ResponseService;
use App\Services\TracerStudyService;
use Illuminate\Http\Request;

class TracerStudyController extends Controller
{
    public function __construct(
        protected TracerStudyService $tracerStudyService
    ) {}

    /**
     * Ambil data tracer study alumni yang sedang login.
     */
    public function show(Request $request): ResponseService
    {
        $tracer = $this->tracerStudyService->getAlumniTracerStudy($request->user());

        if (!$tracer) {
            return ResponseService::make()
                ->message('Belum ada data tracer study yang diisi.')
                ->data(null)
                ->code(200);
        }

        return ResponseService::make()
            ->message('Data tracer study berhasil diambil.')
            ->data(new TracerStudyResource($tracer))
            ->code(200);
    }

    /**
     * Simpan atau perbarui data tracer study alumni.
     */
    public function store(StoreTracerStudyRequest $request): ResponseService
    {
        $tracer = $this->tracerStudyService->submitTracerStudy(
            $request->user(),
            $request->validated()
        );

        return ResponseService::make()
            ->message('Data tracer study berhasil disimpan.')
            ->data(new TracerStudyResource($tracer))
            ->code(201);
    }
}
