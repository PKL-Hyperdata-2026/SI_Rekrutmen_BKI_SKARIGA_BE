<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMajorRequest;
use App\Http\Requests\UpdateMajorRequest;
use App\Http\Resources\MajorResource;
use App\Models\Major;
use App\Services\MajorService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MajorController extends Controller
{
    public function __construct(
        protected MajorService $majorService,
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $filters = $request->only([
            'search',
            'department_id',
            'is_active',
            'sort_by',
            'sort_dir',
        ]);

        $perPage = $request->integer('per_page', 15);
        $majors = $this->majorService->getMajors($filters, $perPage);

        return $this->response
            ->message('Daftar jurusan berhasil diambil.')
            ->data(MajorResource::collection($majors)->response()->getData(true));
    }

    public function options(): Responsable
    {
        $options = $this->majorService->getFormOptions();

        return $this->response
            ->message('Opsi formulir jurusan berhasil diambil.')
            ->data($options);
    }

    public function show(Major $major): Responsable
    {
        $major->load('department');

        return $this->response
            ->message('Detail jurusan berhasil diambil.')
            ->data(new MajorResource($major));
    }

    public function store(StoreMajorRequest $request): Responsable
    {
        $major = $this->majorService->createMajor(
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Jurusan berhasil ditambahkan.')
            ->data(new MajorResource($major))
            ->code(201);
    }

    public function update(UpdateMajorRequest $request, Major $major): Responsable
    {
        $updated = $this->majorService->updateMajor(
            $major,
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Jurusan berhasil diperbarui.')
            ->data(new MajorResource($updated));
    }

    public function destroy(Request $request, Major $major): Responsable
    {
        try {
            $this->majorService->deleteMajor(
                $major,
                $request->user()?->id
            );

            return $this->response
                ->message('Jurusan berhasil dihapus.');
        } catch (InvalidArgumentException $e) {
            return $this->response
                ->success(false)
                ->message($e->getMessage())
                ->code(422);
        }
    }

    public function toggleActive(Request $request, Major $major): Responsable
    {
        $updated = $this->majorService->toggleActive(
            $major,
            $request->user()?->id
        );

        return $this->response
            ->message('Status aktif jurusan berhasil diperbarui.')
            ->data(new MajorResource($updated));
    }
}
