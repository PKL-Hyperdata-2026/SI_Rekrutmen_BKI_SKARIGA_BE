<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\JobPlacementController;
use App\Http\Controllers\Api\JobVacancyController;
use App\Http\Controllers\Api\StudentJobVacancyController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\StudentAlumniController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StandardTypeController;
use App\Http\Controllers\Api\StudentJobApplicationController;
use App\Http\Controllers\Api\TracerStudyController;
use App\Http\Controllers\Api\AdminTracerStudyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('throttle:6,1')->group(function () {
    Route::post('/forgot-password', [AuthController::class, 'forgot']);
    Route::post('/reset-password', [AuthController::class, 'reset']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('notification')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/companies/options', [CompanyController::class, 'options']); // Ambil opsi dropdown form perusahaan (Industri)
        Route::patch('/companies/{company}/toggle-active', [CompanyController::class, 'toggleActive']); // Toggle saklar status aktif/non-aktif perusahaan
        Route::apiResource('companies', CompanyController::class); // CRUD lengkap perusahaan (Index, Store, Show, Update, Delete)
        Route::get('/job-vacancies/options', [JobVacancyController::class, 'options']); // Ambil opsi dropdown form (Perusahaan, Status, Target, Tipe Kerja)
        Route::patch('/job-vacancies/{jobVacancy}/toggle-active', [JobVacancyController::class, 'toggleActive']); // Toggle saklar status aktif/non-aktif lowongan
        Route::apiResource('job-vacancies', JobVacancyController::class)
            ->parameters(['job-vacancies' => 'jobVacancy']); // CRUD lengkap lowongan kerja (Index, Store, Show, Update, Delete)
        Route::get('/students/options', [StudentController::class, 'options']); // Ambil opsi dropdown form siswa (Jurusan, Kelas, Status, Portfolio Type, Perusahaan, Tahun Lulus)
        Route::post('/students/{student}/portfolios', [StudentController::class, 'uploadPortfolio']); // Upload portofolio/berkas siswa
        Route::delete('/students/{student}/portfolios/{portfolio}', [StudentController::class, 'destroyPortfolio']); // Hapus portofolio/berkas siswa
        Route::apiResource('students', StudentController::class)
            ->parameters(['students' => 'student']); // CRUD lengkap data siswa (Index, Store, Show, Update, Delete)
        Route::get('/alumni/options', [StudentAlumniController::class, 'options']); // Ambil opsi dropdown form alumni (Jurusan, Kelas, Status, Perusahaan, Tahun Lulus)
        Route::apiResource('alumni', StudentAlumniController::class)
            ->parameters(['alumni' => 'alumni']); // CRUD lengkap data alumni (Index, Store, Show, Update, Delete)
        Route::get('/departments/options', [DepartmentController::class, 'options']);
        Route::patch('/departments/{department}/toggle-active', [DepartmentController::class, 'toggleActive']);
        Route::apiResource('departments', DepartmentController::class);
        Route::get('/majors/options', [MajorController::class, 'options']);
        Route::patch('/majors/{major}/toggle-active', [MajorController::class, 'toggleActive']);
        Route::apiResource('majors', MajorController::class);
        Route::get('/standard-types', [StandardTypeController::class, 'index']);
        Route::get('/tracer-studies/metrics', [AdminTracerStudyController::class, 'metrics']);
        Route::get('/tracer-studies/options', [AdminTracerStudyController::class, 'options']);
        Route::post('/tracer-studies/sync', [AdminTracerStudyController::class, 'sync']);
        Route::apiResource('tracer-studies', AdminTracerStudyController::class)
            ->parameters(['tracer-studies' => 'tracerStudy']);
        // TODO: API Admin lainnya
    });


    Route::middleware('role:superadmin')->prefix('admin')->group(function () {
        Route::get('/users/options', [UserController::class, 'options']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:hrd')->prefix('hrd')->group(function () {
        Route::get('/job-placements/metrics', [JobPlacementController::class, 'metrics']);
        Route::get('/job-placements/options', [JobPlacementController::class, 'options']);
        Route::get('/students-alumni', [JobPlacementController::class, 'studentsAlumni']);
        Route::apiResource('job-placements', JobPlacementController::class)
            ->parameters(['job-placements' => 'jobPlacement']);
    });

    // Self-Service Siswa & Alumni Portfolio
    Route::middleware('role:siswa,alumni')->prefix('siswa')->group(function () {
        Route::get('/portfolio/profile', [PortfolioController::class, 'getProfile']);
        Route::get('/portfolio/options', [PortfolioController::class, 'getOptions']);
        Route::put('/portfolio/profile', [PortfolioController::class, 'updateProfile']);
        Route::post('/portfolio/upload', [PortfolioController::class, 'uploadPortfolio']);
        Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroyPortfolio']);
    });

    // Lamaran Pekerjaan (Bisa diakses Siswa maupun Alumni)
    Route::middleware('role:siswa,alumni')->group(function () {
        Route::get('/my-applications', [StudentJobApplicationController::class, 'index']);
        Route::get('/my-applications/{id}', [StudentJobApplicationController::class, 'show']);
    });

    // Self-Service Alumni
    Route::middleware('role:alumni')->prefix('alumni')->group(function () {
        Route::get('/portfolio/profile', [PortfolioController::class, 'getProfile']);
        Route::get('/portfolio/options', [PortfolioController::class, 'getOptions']);
        Route::put('/portfolio/profile', [PortfolioController::class, 'updateProfile']);
        Route::post('/portfolio/upload', [PortfolioController::class, 'uploadPortfolio']);
        Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroyPortfolio']);

        // Tracer Study Endpoints
        Route::get('/tracer-study', [TracerStudyController::class, 'show']);
        Route::post('/tracer-study', [TracerStudyController::class, 'store']);
    });

    Route::middleware('role:siswa,alumni')->group(function () {
        Route::get('/my-applications', [StudentJobApplicationController::class, 'index']);
        Route::get('/my-applications/{id}', [StudentJobApplicationController::class, 'show']);
    });
});
