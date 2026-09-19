<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectOptionsRequest;
use App\Http\Requests\StoreStudentPortfolioRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\SelectOptionResource;
use App\Http\Resources\StudentResource;
use App\Models\StudentAlumni;
use App\Models\StudentPortfolio;
use App\Services\ResponseService;
use App\Services\StudentService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $studentService,
        protected ResponseService $response
    ) {}

    public function index(SelectOptionsRequest $request): Responsable
    {
        $filters = $request->only([
            'search',
            'department_id',
            'major_id',
            'class_id',
            'employment_status_id',
            'graduation_year',
            'is_active',
            'sort_by',
            'sort_dir',
            'for_select',
            'eligible',
        ]);

        $perPage = $request->integer('per_page', 15);
        $students = $this->studentService->getStudents($filters, $perPage);

        if ($request->boolean('for_select')) {
            return $this->response
                ->message('Opsi data siswa berhasil diambil.')
                ->data(SelectOptionResource::collection($students)->response()->getData(true));
        }

        return $this->response
            ->message('Daftar data siswa berhasil diambil.')
            ->data(StudentResource::collection($students)->response()->getData(true));
    }

    public function options(): Responsable
    {
        $options = $this->studentService->getFormOptions();

        return $this->response
            ->message('Opsi formulir siswa berhasil diambil.')
            ->data(encrypt_recursive($options));
    }

    public function show(StudentAlumni $student): Responsable
    {
        $data = $this->studentService->show($student);

        return $this->response
            ->message('Detail data siswa berhasil diambil.')
            ->data(new StudentResource($data));
    }

    public function store(StoreStudentRequest $request): Responsable
    {
        $student = $this->studentService->createStudent(
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Data siswa berhasil ditambahkan.')
            ->data(new StudentResource($student))
            ->code(201);
    }

    public function update(UpdateStudentRequest $request, StudentAlumni $student): Responsable
    {
        $updated = $this->studentService->updateStudent(
            $student,
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Data siswa berhasil diperbarui.')
            ->data(new StudentResource($updated));
    }

    public function destroy(Request $request, StudentAlumni $student): Responsable
    {
        $this->studentService->deleteStudent($student, $request->user()?->id);

        return $this->response
            ->message('Data siswa berhasil dihapus.');
    }

    public function uploadPortfolio(StoreStudentPortfolioRequest $request, StudentAlumni $student): Responsable
    {
        $this->studentService->uploadPortfolio(
            $student,
            $request->validated(),
            $request->file('file'),
            $request->user()?->id
        );

        return $this->response
            ->message('Portofolio siswa berhasil diunggah.')
            ->data(new StudentResource($this->studentService->show($student)))
            ->code(201);
    }

    public function destroyPortfolio(Request $request, StudentAlumni $student, StudentPortfolio $portfolio): Responsable
    {
        $this->studentService->deletePortfolio($portfolio, $request->user()?->id);

        return $this->response
            ->message('Portofolio siswa berhasil dihapus.')
            ->data(new StudentResource($this->studentService->show($student)));
    }
}
