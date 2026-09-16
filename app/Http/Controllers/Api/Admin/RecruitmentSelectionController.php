<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecruitmentSelectionIndexRequest;
use App\Http\Resources\RecruitmentSelectionResource;
use App\Services\RecruitmentSelectionService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;

class RecruitmentSelectionController extends Controller
{
    public function __construct(
        protected RecruitmentSelectionService $recruitmentSelectionService,
        protected ResponseService $response
    ) {}

    public function index(RecruitmentSelectionIndexRequest $request): Responsable
    {
        $filters = $request->validated();
        $perPage = $request->integer('per_page', 15);

        $paginator = $this->recruitmentSelectionService->paginate($filters, $perPage);
        $summary = $this->recruitmentSelectionService->getSummary($filters);

        return $this->response
            ->message('Daftar seleksi rekrutmen berhasil diambil.')
            ->data([
                'summary' => $summary,
                'applicants' => RecruitmentSelectionResource::collection($paginator)->response()->getData(true),
            ]);
    }
}
