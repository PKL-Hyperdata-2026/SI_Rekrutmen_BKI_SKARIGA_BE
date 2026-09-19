<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminReportFilterRequest;
use App\Http\Resources\AdminReportResource;
use App\Services\AdminReportService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;

class AdminReportController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
        protected ResponseService $response
    ) {}

    public function options(): Responsable
    {
        $options = $this->reportService->getFilterOptions();

        return $this->response->message('Opsi filter laporan berhasil diambil.')->data(encrypt_recursive($options));
    }

    public function recruitment(AdminReportFilterRequest $request): Responsable
    {
        $result = $this->reportService->getRecruitmentReport($request->validated());

        return $this->response->message('Laporan Rekrutmen berhasil diambil.')->data(new AdminReportResource($result));
    }

    public function attendance(AdminReportFilterRequest $request): Responsable
    {
        $result = $this->reportService->getAttendanceReport($request->validated());

        return $this->response->message('Laporan Absensi berhasil diambil.')->data(new AdminReportResource($result));
    }

    public function absorption(AdminReportFilterRequest $request): Responsable
    {
        $result = $this->reportService->getAbsorptionReport($request->validated());

        return $this->response->message('Laporan Keterserapan berhasil diambil.')->data(new AdminReportResource($result));
    }

    public function tracerStudy(AdminReportFilterRequest $request): Responsable
    {
        $result = $this->reportService->getTracerStudyReport($request->validated());

        return $this->response->message('Laporan Tracer Study berhasil diambil.')->data(new AdminReportResource($result));
    }
}
