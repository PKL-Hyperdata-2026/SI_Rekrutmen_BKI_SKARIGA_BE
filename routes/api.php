<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\RecruitmentSelectionController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminReportController;
use App\Http\Controllers\Api\AdminTracerStudyController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\Hrd\ApplicantReviewController;
use App\Http\Controllers\Api\Hrd\JobPlacementController;
use App\Http\Controllers\Api\Hrd\JobVacancyController as HrdJobVacancyController;
use App\Http\Controllers\Api\Hrd\SelectionResultController;
use App\Http\Controllers\Api\Hrd\TestScheduleController;
use App\Http\Controllers\Api\JobVacancyController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\RecruitmentAttendanceController;
use App\Http\Controllers\Api\StandardTypeController;
use App\Http\Controllers\Api\StudentAlumniController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentJobApplicationController;
use App\Http\Controllers\Api\StudentJobVacancyController;
use App\Http\Controllers\Api\TracerStudyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\AuthController;
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

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
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
        Route::prefix('reports')->group(function () {
            Route::get('/options', [AdminReportController::class, 'options']);
            Route::get('/recruitment', [AdminReportController::class, 'recruitment']);
            Route::get('/attendance', [AdminReportController::class, 'attendance']);
            Route::get('/absorption', [AdminReportController::class, 'absorption']);
            Route::get('/tracer-study', [AdminReportController::class, 'tracerStudy']);
        });
        Route::get('/recruitment-selections', [RecruitmentSelectionController::class, 'index']); // [ADMIN] Seleksi Rekrutmen view-only (Issue #67)
        // TODO: API Admin lainnya

        Route::prefix('attendances')->group(function () {
            Route::get('/vacancies', [RecruitmentAttendanceController::class, 'vacancyOptions']);
            Route::get('/stage-summaries', [RecruitmentAttendanceController::class, 'stageSummaries']);
            Route::get('/queue', [RecruitmentAttendanceController::class, 'queue']);
            Route::get('/history', [RecruitmentAttendanceController::class, 'history']);
            Route::patch('/bulk-validate', [RecruitmentAttendanceController::class, 'bulkValidate']);
            Route::patch('/{attendance}/validate', [RecruitmentAttendanceController::class, 'validateAttendance']);
        });
    });

    Route::middleware('role:superadmin')->prefix('admin')->name('superadmin.')->group(function () {
        Route::get('/users/options', [UserController::class, 'options']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:hrd')->prefix('hrd')->name('hrd.')->group(function () {
        Route::get('/job-placements/metrics', [JobPlacementController::class, 'metrics']);
        Route::get('/job-placements/options', [JobPlacementController::class, 'options']);
        Route::get('/students-alumni', [JobPlacementController::class, 'studentsAlumni']);
        Route::apiResource('job-placements', JobPlacementController::class)
            ->parameters(['job-placements' => 'jobPlacement']);

        // Kelola Lowongan Kerja (HRD Perusahaan)
        Route::get('/job-vacancies/statistics', [HrdJobVacancyController::class, 'statistics']);
        Route::get('/job-vacancies/options', [HrdJobVacancyController::class, 'options']);
        Route::patch('/job-vacancies/{jobVacancy}/toggle-active', [HrdJobVacancyController::class, 'toggleActive']);
        Route::apiResource('job-vacancies', HrdJobVacancyController::class)
            ->parameters(['job-vacancies' => 'jobVacancy']);

        // Kelola Agenda & Jadwal Tes
        Route::get('/test-schedules/options', [TestScheduleController::class, 'options']);
        Route::get('/test-schedules/{id}/participants', [TestScheduleController::class, 'participants']);
        Route::post('/test-schedules/{id}/remind-all', [TestScheduleController::class, 'remindAllParticipants']);
        Route::post('/test-schedules/{id}/participants/{participantId}/remind', [TestScheduleController::class, 'remindParticipant']);
        Route::apiResource('test-schedules', TestScheduleController::class)
            ->parameters(['test-schedules' => 'id']);

        // Review Pelamar & Verifikasi Berkas (HRD Perusahaan)
        Route::get('/applicant-reviews/options', [ApplicantReviewController::class, 'options']);
        Route::post('/applicant-reviews/bulk-review', [ApplicantReviewController::class, 'bulkReview']);
        Route::get('/applicant-reviews', [ApplicantReviewController::class, 'index']);
        Route::get('/applicant-reviews/{id}', [ApplicantReviewController::class, 'show']);
        Route::patch('/applicant-reviews/{id}/review', [ApplicantReviewController::class, 'review']);

        // Input & Evaluasi Hasil Seleksi (HRD Perusahaan)
        Route::get('/selection-results/options', [SelectionResultController::class, 'options']);
        Route::get('/selection-results', [SelectionResultController::class, 'index']);
        Route::post('/selection-results/publish', [SelectionResultController::class, 'publish']);
        Route::post('/selection-results/draft', [SelectionResultController::class, 'draft']);
        Route::post('/selection-results/{id}', [SelectionResultController::class, 'store']);
        Route::patch('/selection-results/{id}/decision', [SelectionResultController::class, 'updateDecision']);
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
        Route::get('/job-vacancies', [StudentJobVacancyController::class, 'index']);
        Route::get('/job-vacancies/options', [StudentJobVacancyController::class, 'options']);
        Route::get('/job-vacancies/{jobVacancy}', [StudentJobVacancyController::class, 'show']);
        Route::post('/job-vacancies/{jobVacancy}/apply', [StudentJobVacancyController::class, 'apply']);

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
});
