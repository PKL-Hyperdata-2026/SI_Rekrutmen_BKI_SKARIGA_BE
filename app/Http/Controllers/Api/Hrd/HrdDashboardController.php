<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hrd;

use App\Http\Controllers\Controller;
use App\Services\HrdDashboardService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class HrdDashboardController extends Controller
{
    public function __construct(
        protected HrdDashboardService $dashboardService,
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $data = $this->dashboardService->getDashboardData((int) $request->user()->id);

        return $this->response
            ->message('Data dashboard HRD berhasil diambil.')
            ->data($data);
    }
}
