<?php

use App\Http\Controllers\Api\JobPlacementController;
use App\Http\Controllers\Api\JobVacancyController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\StudentAlumniController;
use App\Http\Controllers\Api\StudentController;
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

    Route::apiResource('job-vacancies', JobVacancyController::class);

    Route::prefix('notification')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/job-vacancies/options', [JobVacancyController::class, 'options']); // Ambil opsi dropdown form (Perusahaan, Status, Target, Tipe Kerja)
        Route::patch('/job-vacancies/{jobVacancy}/toggle-active', [JobVacancyController::class, 'toggleActive']); // Toggle saklar status aktif/non-aktif lowongan
        Route::apiResource('job-vacancies', JobVacancyController::class); // CRUD lengkap lowongan kerja (Index, Store, Show, Update, Delete)
        Route::get('/students/options', [StudentController::class, 'options']); // Ambil opsi dropdown form siswa (Jurusan, Kelas, Status, Portfolio Type, Perusahaan, Tahun Lulus)
        Route::post('/students/{student}/portfolios', [StudentController::class, 'uploadPortfolio']); // Upload portofolio/berkas siswa
        Route::delete('/students/{student}/portfolios/{portfolio}', [StudentController::class, 'destroyPortfolio']); // Hapus portofolio/berkas siswa
        Route::apiResource('students', StudentController::class)
            ->parameters(['students' => 'student']); // CRUD lengkap data siswa (Index, Store, Show, Update, Delete)
        Route::get('/alumni/options', [StudentAlumniController::class, 'options']); // Ambil opsi dropdown form alumni (Jurusan, Kelas, Status, Perusahaan, Tahun Lulus)
        Route::apiResource('alumni', StudentAlumniController::class)
            ->parameters(['alumni' => 'alumni']); // CRUD lengkap data alumni (Index, Store, Show, Update, Delete)
        Route::get('/job-placements/options', [JobPlacementController::class, 'options']); // Ambil opsi dropdown form penempatan kerja
        Route::apiResource('job-placements', JobPlacementController::class)
            ->parameters(['job-placements' => 'jobPlacement']); // CRUD lengkap penempatan kerja (Index, Store, Show, Update, Delete)
        // TODO: API Admin lainnya
    });

    Route::middleware('role:superadmin')->prefix('admin')->group(function () {
        Route::get('/users/options', [UserController::class, 'options']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:hrd')->prefix('hrd')->group(function () {
        // TODO: API untuk HRD
    });

    Route::middleware('role:siswa')->prefix('siswa')->group(function () {
        Route::get('/portfolio/profile', [PortfolioController::class, 'getProfile']);
        Route::get('/portfolio/options', [PortfolioController::class, 'getOptions']);
        Route::put('/portfolio/profile', [PortfolioController::class, 'updateProfile']);
        Route::post('/portfolio/upload', [PortfolioController::class, 'uploadPortfolio']);
        Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroyPortfolio']);
    });

    Route::middleware('role:alumni')->prefix('alumni')->group(function () {
        Route::get('/portfolio/profile', [PortfolioController::class, 'getProfile']);
        Route::get('/portfolio/options', [PortfolioController::class, 'getOptions']);
        Route::put('/portfolio/profile', [PortfolioController::class, 'updateProfile']);
        Route::post('/portfolio/upload', [PortfolioController::class, 'uploadPortfolio']);
        Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroyPortfolio']);
    });
});
