<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDashboardFilterRequest;
use App\Http\Resources\AdminDashboardResource;
use App\Services\AdminDashboardService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboardService,
        protected ResponseService $response
    ) {}

    public function index(AdminDashboardFilterRequest $request): Responsable
    {
        $data = $this->dashboardService->getDashboardData();

        return $this->response
            ->message('Data dashboard admin berhasil diambil.')
            ->data(new AdminDashboardResource($data));
    }
}
