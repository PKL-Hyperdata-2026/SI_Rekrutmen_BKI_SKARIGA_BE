<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetStudentAlumniRequest;
use App\Http\Requests\StoreAlumniRequest;
use App\Http\Requests\UpdateAlumniRequest;
use App\Http\Resources\StudentAlumniResource;
use App\Models\StudentAlumni;
use App\Services\ResponseService;
use App\Services\StudentAlumniService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class StudentAlumniController extends Controller
{
    public function __construct(
        protected StudentAlumniService $studentAlumniService,
        protected ResponseService $response
    ) {}

    public function index(GetStudentAlumniRequest $request): Responsable
    {
        $filters = $request->validated();

        $perPage = $request->integer('per_page', 15);
        $alumni = $this->studentAlumniService->index($filters, $perPage);

        return $this->response
            ->message('Daftar alumni berhasil diambil.')
            ->data(StudentAlumniResource::collection($alumni)->response()->getData(true));
    }

    public function options(): Responsable
    {
        $options = $this->studentAlumniService->getFormOptions();

        return $this->response
            ->message('Opsi formulir alumni berhasil diambil.')
            ->data(encrypt_recursive($options));
    }

    public function show(StudentAlumni $alumni): Responsable
    {
        return $this->response
            ->message('Detail alumni berhasil diambil.')
            ->data(new StudentAlumniResource($this->studentAlumniService->show($alumni)));
    }

    public function store(StoreAlumniRequest $request): Responsable
    {
        $alumni = $this->studentAlumniService->create($request->validated(), $request->user()?->id);

        return $this->response
            ->message('Data alumni berhasil ditambahkan.')
            ->data(new StudentAlumniResource($alumni))
            ->code(201);
    }

    public function update(UpdateAlumniRequest $request, StudentAlumni $alumni): Responsable
    {
        $updated = $this->studentAlumniService->update($alumni, $request->validated(), $request->user()?->id);

        return $this->response
            ->message('Data alumni berhasil diperbarui.')
            ->data(new StudentAlumniResource($updated));
    }

    public function destroy(Request $request, StudentAlumni $alumni): Responsable
    {
        $this->studentAlumniService->delete($alumni, $request->user()?->id);

        return $this->response->message('Data alumni berhasil dihapus.');
    }
}
