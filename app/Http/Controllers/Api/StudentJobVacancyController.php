<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyJobVacancyRequest;
use App\Http\Requests\GetStudentJobVacanciesRequest;
use App\Http\Resources\JobVacancyResource;
use App\Http\Resources\StudentJobApplicationResource;
use App\Models\JobVacancy;
use App\Services\ResponseService;
use App\Services\StudentJobVacancyService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class StudentJobVacancyController extends Controller
{
    public function __construct(protected StudentJobVacancyService $service, protected ResponseService $response) {}

    public function index(GetStudentJobVacanciesRequest $request): Responsable
    {
        $user = $request->user();
        $studentProfile = $user->studentAlumni;
        $studentId = $studentProfile?->id;
        $majorId = $studentProfile?->major_id;

        $paginator = $this->service->getStudentVacancies(
            $request->validated(),
            $request->integer('per_page', 12),
            $user->role,
            $studentId,
            $majorId
        );
        return $this->response->message('Daftar lowongan kerja berhasil diambil.')
            ->data(JobVacancyResource::collection($paginator)->response()->getData(true));
    }

    public function show(Request $request, string $jobVacancy): Responsable
    {
        $studentId = $request->user()->studentAlumni?->id;
        $vacancy = $this->service->getStudentVacancyDetail($jobVacancy, $studentId);

        return $this->response->message('Detail lowongan kerja berhasil diambil.')
            ->data(new JobVacancyResource($vacancy));
    }

    public function options(Request $request): Responsable
    {
        $user = $request->user();
        $role = $user->role;
        $majorId = $user->studentAlumni?->major_id;

        return $this->response->message('Opsi filter lowongan berhasil diambil.')
            ->data(encrypt_recursive($this->service->getFormOptionsForStudent($role, $majorId)));
    }

    public function apply(ApplyJobVacancyRequest $request, JobVacancy $jobVacancy): Responsable
    {
        $app = $this->service->applyToVacancy($jobVacancy, $request->user()->id, $request->user()->role, $request->validated()['notes'] ?? null);
        return $this->response->message('Lamaran berhasil dikirim.')->data(new StudentJobApplicationResource($app->load(['jobVacancy.company', 'status'])))->code(201);
    }
}
