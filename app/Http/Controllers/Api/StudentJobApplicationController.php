<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentJobApplicationResource;
use App\Services\ResponseService;
use App\Services\StudentJobApplicationService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class StudentJobApplicationController extends Controller
{
    public function __construct(
        protected StudentJobApplicationService $studentJobApplicationService,
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $filters = $request->only([
            'status_id',
            'per_page',
        ]);

        $applications = $this->studentJobApplicationService->getMyApplications(
            (int) $request->user()->id,
            $filters
        );

        return $this->response
            ->message('Daftar lamaran saya berhasil diambil.')
            ->data(StudentJobApplicationResource::collection($applications)->response()->getData(true));
    }

    public function show(Request $request, int $id): Responsable
    {
        $application = $this->studentJobApplicationService->getMyApplicationDetail(
            (int) $request->user()->id,
            $id
        );

        return $this->response
            ->message('Detail lamaran saya berhasil diambil.')
            ->data(new StudentJobApplicationResource($application));
    }
}
