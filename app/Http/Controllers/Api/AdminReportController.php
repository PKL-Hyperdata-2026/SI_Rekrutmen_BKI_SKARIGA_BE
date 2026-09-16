<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
        protected ResponseService $response
    ) {}

    public function options(): Responsable
    {
        $options = $this->reportService->getFilterOptions();

        return $this->response->message('Opsi filter laporan berhasil diambil.')->data($options);
    }

    public function recruitment(Request $request): Responsable
    {
        $filters = $request->only(['start_date', 'end_date', 'applicant_type']);
        $result = $this->reportService->getRecruitmentReport($filters);

        return $this->response->message('Laporan Rekrutmen berhasil diambil.')->data($result);
    }

    public function attendance(Request $request): Responsable
    {
        $filters = $request->only(['start_date', 'end_date', 'company_id']);
        $result = $this->reportService->getAttendanceReport($filters);

        return $this->response->message('Laporan Absensi berhasil diambil.')->data($result);
    }

    public function absorption(Request $request): Responsable
    {
        $filters = $request->only(['start_date', 'end_date', 'major_id']);
        $result = $this->reportService->getAbsorptionReport($filters);

        return $this->response->message('Laporan Keterserapan berhasil diambil.')->data($result);
    }

    public function tracerStudy(Request $request): Responsable
    {
        $filters = $request->only(['start_date', 'end_date', 'graduation_year']);
        $result = $this->reportService->getTracerStudyReport($filters);

        return $this->response->message('Laporan Tracer Study berhasil diambil.')->data($result);
    }
}
