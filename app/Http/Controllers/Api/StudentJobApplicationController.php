<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetStudentJobApplicationRequest;
use App\Http\Resources\StudentJobApplicationResource;
use App\Services\ResponseService;
use App\Services\StudentJobApplicationService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Auth;

class StudentJobApplicationController extends Controller
{
    public function __construct(
        protected StudentJobApplicationService $studentJobApplicationService,
        protected ResponseService $response
    ) {}

    public function index(GetStudentJobApplicationRequest $request): Responsable
    {
        $filters = $request->validated();

        $applications = $this->studentJobApplicationService->getMyApplications(
            (int) Auth::id(),
            $filters
        );

        return $this->response
            ->message('Daftar lamaran saya berhasil diambil.')
            ->data(StudentJobApplicationResource::collection($applications)->response()->getData(true));
    }

    public function show(int $id): Responsable
    {
        $application = $this->studentJobApplicationService->getMyApplicationDetail(
            (int) Auth::id(),
            $id
        );

        return $this->response
            ->message('Detail lamaran saya berhasil diambil.')
            ->data(new StudentJobApplicationResource($application));
    }
}
