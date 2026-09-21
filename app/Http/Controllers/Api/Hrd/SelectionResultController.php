<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\HrdSelectionResultIndexRequest;
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

    public function store(Request $request, int $applicationId): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $request->validate([
            'psychotest_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mcu_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'decision' => ['nullable', 'string', 'in:diterima,tidak_diterima,cadangan,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'letter_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $letterFile = $request->file('letter_file');

        $result = $this->selectionResultService->saveEvaluation(
            $company->id,
            $applicationId,
            $request->all(),
            $letterFile,
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Hasil seleksi pelamar berhasil disimpan.')
            ->data(new HrdSelectionResultResource($result));
    }

    public function updateDecision(Request $request, int $applicationId): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $request->validate([
            'decision' => ['required', 'string', 'in:diterima,tidak_diterima,cadangan,pending'],
        ]);

        $result = $this->selectionResultService->updateDecision(
            $company->id,
            $applicationId,
            (string) $request->input('decision'),
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Keputusan seleksi berhasil diubah.')
            ->data(new HrdSelectionResultResource($result));
    }

    public function publish(Request $request): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $request->validate([
            'job_vacancy_id' => ['required', 'integer', 'exists:job_vacancies,id'],
        ]);

        $count = $this->selectionResultService->publishResults(
            $company->id,
            (int) $request->input('job_vacancy_id'),
            (int) $request->user()?->id
        );

        return $this->response
            ->message("Hasil seleksi berhasil dipublikasikan dan disinkronkan ke BKK ({$count} pelamar diproses).")
            ->data(['published_count' => $count]);
    }

    public function draft(Request $request): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $request->validate([
            'job_vacancy_id' => ['required', 'integer', 'exists:job_vacancies,id'],
        ]);

        $count = $this->selectionResultService->saveDraft(
            $company->id,
            (int) $request->input('job_vacancy_id'),
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Status hasil seleksi berhasil disimpan sebagai draft.')
            ->data(['draft_count' => $count]);
    }
}
