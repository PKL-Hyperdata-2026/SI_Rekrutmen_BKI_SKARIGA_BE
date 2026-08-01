<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobVacancyRequest;
use App\Http\Requests\UpdateJobVacancyRequest;
use App\Http\Resources\JobVacancyResource;
use App\Models\JobVacancy;
use App\Services\JobVacancyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobVacancyController extends Controller
{
    public function __construct(protected JobVacancyService $jobVacancyService) {}

    public function index(Request $request): JsonResponse
    {
        $query = JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }
        if ($request->filled('job_type_id')) {
            $query->where('job_type_id', $request->job_type_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('work_location', 'like', "%{$search}%");
            });
        }

        $vacancies = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Job vacancies retrieved successfully.',
            'data' => JobVacancyResource::collection($vacancies)->response()->getData(true),
        ]);
    }

    public function store(StoreJobVacancyRequest $request): JsonResponse
    {
        $vacancy = $this->jobVacancyService->createJobVacancy(
            $request->validated(),
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Job vacancy created successfully.',
            'data' => new JobVacancyResource($vacancy),
        ], 201);
    }

    public function show(string $idOrSlug): JsonResponse
    {
        $vacancy = JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors'])
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Job vacancy details retrieved successfully.',
            'data' => new JobVacancyResource($vacancy),
        ]);
    }

    public function update(UpdateJobVacancyRequest $request, JobVacancy $jobVacancy): JsonResponse
    {
        $updated = $this->jobVacancyService->updateJobVacancy(
            $jobVacancy,
            $request->validated(),
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Job vacancy updated successfully.',
            'data' => new JobVacancyResource($updated),
        ]);
    }

    public function destroy(Request $request, JobVacancy $jobVacancy): JsonResponse
    {
        $this->jobVacancyService->deleteJobVacancy($jobVacancy, $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Job vacancy deleted successfully.',
        ]);
    }
}
