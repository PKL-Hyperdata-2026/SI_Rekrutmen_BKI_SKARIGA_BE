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
        $paginator = $this->service->getStudentVacancies($request->validated(), $request->integer('per_page', 12), $request->user()->role);
        return $this->response->message('Daftar lowongan kerja berhasil diambil.')
            ->data(JobVacancyResource::collection($paginator)->response()->getData(true));
    }

    public function show(Request $request, string $jobVacancy): Responsable
    {
        $vacancy = $this->service->getStudentVacancyDetail($jobVacancy);
        $studentId = $request->user()->studentAlumni?->id;
        $hasApplied = $this->service->hasStudentApplied($vacancy->id, $studentId);

        $data = (new JobVacancyResource($vacancy))->toArray($request);
        $data['hasApplied'] = $hasApplied;

        return $this->response->message('Detail lowongan kerja berhasil diambil.')->data($data);
    }

    public function options(): Responsable
    {
        return $this->response->message('Opsi filter lowongan berhasil diambil.')->data($this->service->getFormOptionsForStudent());
    }

    public function apply(ApplyJobVacancyRequest $request, JobVacancy $jobVacancy): Responsable
    {
        $app = $this->service->applyToVacancy($jobVacancy, $request->user()->id, $request->user()->role, $request->validated()['notes'] ?? null);
        return $this->response->message('Lamaran berhasil dikirim.')->data(new StudentJobApplicationResource($app->load(['jobVacancy.company', 'status'])))->code(201);
    }
}
