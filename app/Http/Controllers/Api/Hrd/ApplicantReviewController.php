<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\HrdApplicantBulkReviewRequest;
use App\Http\Requests\HrdApplicantReviewActionRequest;
use App\Http\Requests\HrdApplicantReviewIndexRequest;
use App\Http\Resources\HrdApplicantReviewResource;
use App\Services\HrdApplicantReviewService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class ApplicantReviewController extends Controller
{
    public function __construct(
        protected HrdApplicantReviewService $reviewService,
        protected ResponseService $response
    ) {}

    public function index(HrdApplicantReviewIndexRequest $request): Responsable
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
        $paginator = $this->reviewService->paginate($company->id, $filters, $perPage);
        $summary = $this->reviewService->getSummary($company->id, $filters);
        $options = $this->reviewService->getOptions($company->id);

        return $this->response
            ->message('Daftar pelamar berhasil diambil.')
            ->data([
                'summary' => $summary,
                'applicants' => HrdApplicantReviewResource::collection($paginator)->response()->getData(true),
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

        return $this->response
            ->message('Opsi filter pelamar berhasil diambil.')
            ->data($this->reviewService->getOptions($company->id));
    }

    public function show(Request $request, int $id): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $application = $this->reviewService->getDetail($company->id, $id);

        return $this->response
            ->message('Detail pelamar berhasil diambil.')
            ->data(new HrdApplicantReviewResource($application));
    }

    public function review(HrdApplicantReviewActionRequest $request, int $id): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $validated = $request->validated();
        $application = $this->reviewService->review($company->id, $id, $validated, $request->user()?->id);

        $message = $validated['decision'] === 'lolos'
            ? 'Pelamar berhasil diloloskan pada seleksi administrasi.'
            : 'Pelamar ditolak pada seleksi administrasi.';

        return $this->response
            ->message($message)
            ->data(new HrdApplicantReviewResource($application));
    }

    public function bulkReview(HrdApplicantBulkReviewRequest $request): Responsable
    {
        $company = $request->user()?->company;

        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $result = $this->reviewService->bulkReview($company->id, $request->validated(), $request->user()?->id);

        return $this->response
            ->message('Review massal pelamar selesai diproses.')
            ->data($result);
    }
}
