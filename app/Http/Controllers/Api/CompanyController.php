<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService,
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $filters = $request->only([
            'search',
            'industry_id',
            'is_active',
            'sort_by',
            'sort_dir',
        ]);

        $perPage = $request->integer('per_page', 15);
        $companies = $this->companyService->index($filters, $perPage);

        return $this->response
            ->message('Daftar perusahaan berhasil diambil.')
            ->data(CompanyResource::collection($companies)->response()->getData(true));
    }

    public function options(): Responsable
    {
        $options = $this->companyService->getFormOptions();

        return $this->response
            ->message('Opsi formulir perusahaan berhasil diambil.')
            ->data($options);
    }

    public function show(Company $company): Responsable
    {
        return $this->response
            ->message('Detail perusahaan berhasil diambil.')
            ->data(new CompanyResource($this->companyService->show($company)));
    }

    public function store(StoreCompanyRequest $request): Responsable
    {
        $company = $this->companyService->create($request->validated(), $request->user()?->id);

        return $this->response
            ->message('Data perusahaan berhasil ditambahkan.')
            ->data(new CompanyResource($company))
            ->code(201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): Responsable
    {
        $updated = $this->companyService->update($company, $request->validated(), $request->user()?->id);

        return $this->response
            ->message('Data perusahaan berhasil diperbarui.')
            ->data(new CompanyResource($updated));
    }

    public function destroy(Request $request, Company $company): Responsable
    {
        $this->companyService->delete($company, $request->user()?->id);

        return $this->response->message('Data perusahaan berhasil dihapus.');
    }

    public function toggleActive(Request $request, Company $company): Responsable
    {
        $updated = $this->companyService->toggleActive($company, $request->user()?->id);
        $statusText = $updated->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->response
            ->message("Status perusahaan berhasil {$statusText}.")
            ->data(new CompanyResource($updated));
    }
}
