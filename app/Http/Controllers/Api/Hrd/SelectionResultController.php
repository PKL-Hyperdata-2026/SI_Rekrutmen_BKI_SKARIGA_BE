<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\HrdSelectionResultIndexRequest;
use App\Http\Requests\PublishHrdSelectionResultRequest;
use App\Http\Requests\StoreHrdSelectionResultRequest;
use App\Http\Requests\UpdateHrdSelectionDecisionRequest;
use App\Http\Resources\HrdSelectionResultResource;
use App\Services\HrdSelectionResultService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class SelectionResultController extends Controller
{
    public function __construct(
        protected HrdSelectionResultService $selectionResultService,
        protected ResponseService $response
    ) {}

    public function index(HrdSelectionResultIndexRequest $request): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $filters = $request->validated();
        $perPage = $request->integer('per_page', 15);
        $paginator = $this->selectionResultService->paginate($company->id, $filters, $perPage);
        $summary = $this->selectionResultService->getSummary($company->id, $filters);
        $options = $this->selectionResultService->getOptions($company->id);

        return $this->response
            ->message('Daftar peserta seleksi berhasil diambil.')
            ->data([
                'summary' => $summary,
                'applicants' => HrdSelectionResultResource::collection($paginator)->response()->getData(true),
                'filters' => $options,
            ]);
    }

    public function options(Request $request): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $options = $this->selectionResultService->getOptions($company->id);

        return $this->response
            ->message('Opsi filter seleksi berhasil diambil.')
            ->data(encrypt_recursive($options));
    }

    public function store(StoreHrdSelectionResultRequest $request, int $applicationId): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $letterFile = $request->file('letter_file');

        $result = $this->selectionResultService->saveEvaluation(
            $company->id,
            $applicationId,
            $request->validated(),
            $letterFile,
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Hasil seleksi pelamar berhasil disimpan.')
            ->data(new HrdSelectionResultResource($result));
    }

    public function updateDecision(UpdateHrdSelectionDecisionRequest $request, int $applicationId): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $result = $this->selectionResultService->updateDecision(
            $company->id,
            $applicationId,
            (string) $request->validated('decision'),
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Keputusan seleksi berhasil diubah.')
            ->data(new HrdSelectionResultResource($result));
    }

    public function publish(PublishHrdSelectionResultRequest $request): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $count = $this->selectionResultService->publishResults(
            $company->id,
            (int) $request->validated('job_vacancy_id'),
            (int) $request->user()?->id
        );

        return $this->response
            ->message("Hasil seleksi berhasil dipublikasikan dan disinkronkan ke BKK ({$count} pelamar diproses).")
            ->data(['published_count' => $count]);
    }

    public function draft(PublishHrdSelectionResultRequest $request): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $count = $this->selectionResultService->saveDraft(
            $company->id,
            (int) $request->validated('job_vacancy_id'),
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Status hasil seleksi berhasil disimpan sebagai draft.')
            ->data(['draft_count' => $count]);
    }
}
