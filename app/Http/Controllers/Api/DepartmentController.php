<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectOptionsRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\SelectOptionResource;
use App\Models\Department;
use App\Services\DepartmentService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService,
        protected ResponseService $response
    ) {}

    public function index(SelectOptionsRequest $request): Responsable
    {
        $filters = $request->only([
            'search',
            'is_active',
            'sort_by',
            'sort_dir',
            'for_select',
        ]);

        $perPage = $request->integer('per_page', 15);
        $departments = $this->departmentService->getDepartments($filters, $perPage);

        if ($request->boolean('for_select')) {
            return $this->response
                ->message('Opsi departemen berhasil diambil.')
                ->data(SelectOptionResource::collection($departments)->response()->getData(true));
        }

        return $this->response
            ->message('Daftar departemen berhasil diambil.')
            ->data(DepartmentResource::collection($departments)->response()->getData(true));
    }

    public function options(): Responsable
    {
        $options = $this->departmentService->getFormOptions();

        return $this->response
            ->message('Opsi formulir departemen berhasil diambil.')
            ->data($options);
    }

    public function show(Department $department): Responsable
    {
        $department->load('majors');

        return $this->response
            ->message('Detail departemen berhasil diambil.')
            ->data(new DepartmentResource($department));
    }

    public function store(StoreDepartmentRequest $request): Responsable
    {
        $department = $this->departmentService->createDepartment(
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Departemen berhasil ditambahkan.')
            ->data(new DepartmentResource($department))
            ->code(201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): Responsable
    {
        $updated = $this->departmentService->updateDepartment(
            $department,
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Departemen berhasil diperbarui.')
            ->data(new DepartmentResource($updated));
    }

    public function destroy(Request $request, Department $department): Responsable
    {
        try {
            $this->departmentService->deleteDepartment(
                $department,
                $request->user()?->id
            );

            return $this->response
                ->message('Departemen berhasil dihapus.');
        } catch (InvalidArgumentException $e) {
            return $this->response
                ->success(false)
                ->message($e->getMessage())
                ->code(422);
        }
    }

    public function toggleActive(Request $request, Department $department): Responsable
    {
        $updated = $this->departmentService->toggleActive(
            $department,
            $request->user()?->id
        );

        return $this->response
            ->message('Status aktif departemen berhasil diperbarui.')
            ->data(new DepartmentResource($updated));
    }
}
