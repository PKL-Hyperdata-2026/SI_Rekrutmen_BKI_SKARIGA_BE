<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentPortfolioRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Resources\StudentMyProfileResource;
use App\Http\Resources\StudentPortfolioResource;
use App\Models\StudentPortfolio;
use App\Services\ResponseService;
use App\Services\StudentPortfolioService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class SiswaPortfolioController extends Controller
{
    public function __construct(
        protected StudentPortfolioService $portfolioService,
        protected ResponseService $response
    ) {}

    public function getProfile(Request $request): Responsable
    {
        $student = $this->portfolioService->getProfileForUser($request->user());

        return $this->response
            ->message('Data profil dan portofolio berhasil diambil.')
            ->data(new StudentMyProfileResource($student));
    }

    public function getOptions(): Responsable
    {
        $options = $this->portfolioService->getFormOptions();

        return $this->response
            ->message('Opsi formulir portofolio berhasil diambil.')
            ->data($options);
    }

    public function updateProfile(UpdateStudentProfileRequest $request): Responsable
    {
        $updated = $this->portfolioService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return $this->response
            ->message('Data profil dan akademik berhasil diperbarui.')
            ->data(new StudentMyProfileResource($updated));
    }

    public function uploadPortfolio(StoreStudentPortfolioRequest $request): Responsable
    {
        $portfolio = $this->portfolioService->uploadPortfolio(
            $request->user(),
            $request->validated(),
            $request->file('file')
        );

        return $this->response
            ->message('Dokumen berhasil diunggah.')
            ->data(new StudentPortfolioResource($portfolio))
            ->code(201);
    }

    public function destroyPortfolio(Request $request, StudentPortfolio $portfolio): Responsable
    {
        $this->portfolioService->deletePortfolio($request->user(), $portfolio);

        return $this->response
            ->message('Dokumen berhasil dihapus.');
    }
}
