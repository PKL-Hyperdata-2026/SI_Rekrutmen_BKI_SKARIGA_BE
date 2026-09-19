# Backend Codebase Reference (Laravel 13 API)

Deep, factual reference for AI agents and developers. **Last verified: 2026-09-19.**
If you modify code that alters any architecture, models, routes, or services documented here, update this file in the same change.
Operational instructions & boundaries: [`AGENTS.md`](./AGENTS.md).

> **Staleness rule:** If this file is >4 weeks old, verify schema and routes against `database/migrations/` and `routes/api.php` before trusting it.

## 1. Stack

| Layer | Technology | Details |
|---|---|---|
| Framework | Laravel 13 | PHP 8.3+, Strict Types enabled |
| Runtime & Server | Laravel Octane & RoadRunner | High-performance stateful execution model |
| Real-time WebSockets | Laravel Reverb | Native WebSocket broadcasting server (`^1.0`) |
| Database | PostgreSQL | Relational DB with foreign keys, indexes, and transactions |
| Authentication | Laravel Sanctum | Stateful/Bearer token API authentication |
| Pattern | Service-Repository / Action | Thin Controllers, Fat Services, API Resources |

## 2. Architecture (How Things Connect)

```text
HTTP Request (Frontend Client)
  → routes/api.php (Route declaration + middleware: auth:sanctum, role:X)
  → app/Http/Middleware/ (RBAC, DecryptRequest)
  → app/Http/Controllers/ (Thin HTTP dispatcher)
      → FormRequest (app/Http/Requests/ - input validation & authorization)
      → Service Layer (app/Services/ - business rules, DB transactions, external integrations)
          → Eloquent Models (app/Models/ - query database, relations, scopes)
          → PostgreSQL Database
          → Real-time Broadcast (App\Events\NotificationSent → Laravel Reverb WebSockets)
      → API JsonResource (app/Http/Resources/ - formatting & filtering response fields)
      → ResponseService (wraps into standard JSON envelope)
  → HTTP JSON Response to Client
```

## 3. Project Structure

```text
backend/app/
├── Console/
│   └── Commands/
│       └── DevCommand.php                             # Dev runner helper command
├── Events/
│   └── NotificationSent.php                           # Real-time WebSocket broadcasting event via Reverb
├── Helpers/
│   └── encryption.php                                 # ID encryption & decryption helpers (AES-256-CBC)
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── Admin/
│   │   │   │   └── RecruitmentSelectionController.php # [ADMIN] View-only seleksi rekrutmen: summary + paginasi + filter
│   │   │   ├── Hrd/
│   │   │   │   ├── JobPlacementController.php         # HRD CRUD penempatan kerja & metrik evaluasi
│   │   │   │   ├── JobVacancyController.php           # HRD CRUD lowongan kerja & statistik
│   │   │   │   └── TestScheduleController.php         # HRD CRUD jadwal tes, list peserta, & reminder
│   │   │   ├── AdminReportController.php              # Admin rekapitulasi laporan (rekrutmen, absensi, keterserapan, tracer)
│   │   │   ├── AdminTracerStudyController.php         # Admin CRUD tracer study, metrics, options, & sync alumni
│   │   │   ├── CompanyController.php                  # Admin CRUD perusahaan mitra & options
│   │   │   ├── DepartmentController.php               # Admin CRUD departemen vokasi & toggle active
│   │   │   ├── JobVacancyController.php               # Admin CRUD & toggle active lowongan kerja
│   │   │   ├── MajorController.php                    # Admin CRUD jurusan & toggle active
│   │   │   ├── NotificationController.php             # User notifications listing, unread count, & mark read
│   │   │   ├── PortfolioController.php                # Self-service portfolio profile, options, upload/delete dokumen
│   │   │   ├── RecruitmentAttendanceController.php    # Admin validasi presensi (antrean, riwayat, bulk-validate)
│   │   │   ├── StandardTypeController.php             # Generic async-select options (?category=&search=&per_page=)
│   │   │   ├── StudentAlumniController.php            # Admin CRUD data alumni (upgrade akun siswa)
│   │   │   ├── StudentController.php                  # Admin CRUD data siswa kelas 12 aktif & portfolio
│   │   │   ├── StudentJobApplicationController.php    # Siswa/alumni daftar & detail lamaran saya
│   │   │   ├── StudentJobVacancyController.php        # Siswa/alumni eksplorasi lowongan kerja & submit lamaran
│   │   │   ├── TracerStudyController.php              # Self-service tracer study submission & detail (role alumni)
│   │   │   └── UserController.php                     # Superadmin user management & password override
│   │   ├── Auth/
│   │   │   └── AuthController.php                     # Login, logout, me endpoint + forgot/reset password
│   │   └── Controller.php                             # Base Laravel controller
│   ├── Middleware/
│   │   ├── DecryptRequest.php                         # Otomatis mendekripsi ID terenkripsi pada request
│   │   └── RBAC.php                                   # Role-Based Access Control middleware (aliased as 'role')
│   ├── Requests/
│   │   ├── ApplyJobVacancyRequest.php
│   │   ├── BulkValidateAttendanceRequest.php
│   │   ├── ForgotPasswordRequest.php
│   │   ├── GetAttendanceQueueRequest.php
│   │   ├── GetAttendanceStageSummariesRequest.php
│   │   ├── GetStudentJobApplicationRequest.php
│   │   ├── GetStudentJobVacanciesRequest.php
│   │   ├── LoginRequest.php
│   │   ├── RecruitmentSelectionIndexRequest.php
│   │   ├── ResetPasswordRequest.php
│   │   ├── ResetUserPasswordRequest.php
│   │   ├── SelectOptionsRequest.php
│   │   ├── StandardTypeOptionsRequest.php
│   │   ├── StoreAdminTracerStudyRequest.php
│   │   ├── StoreAlumniRequest.php
│   │   ├── StoreCompanyRequest.php
│   │   ├── StoreDepartmentRequest.php
│   │   ├── StoreHrdJobVacancyRequest.php
│   │   ├── StoreHrdTestScheduleRequest.php
│   │   ├── StoreJobPlacementRequest.php
│   │   ├── StoreJobVacancyRequest.php
│   │   ├── StoreMajorRequest.php
│   │   ├── StoreStudentPortfolioRequest.php
│   │   ├── StoreStudentRequest.php
│   │   ├── StoreTracerStudyRequest.php
│   │   ├── StoreUserRequest.php
│   │   ├── UpdateAdminTracerStudyRequest.php
│   │   ├── UpdateAlumniRequest.php
│   │   ├── UpdateCompanyRequest.php
│   │   ├── UpdateDepartmentRequest.php
│   │   ├── UpdateHrdJobVacancyRequest.php
│   │   ├── UpdateHrdTestScheduleRequest.php
│   │   ├── UpdateJobPlacementRequest.php
│   │   ├── UpdateJobVacancyRequest.php
│   │   ├── UpdateMajorRequest.php
│   │   ├── UpdateStudentProfileRequest.php
│   │   ├── UpdateStudentRequest.php
│   │   ├── UpdateUserRequest.php
│   │   └── ValidateAttendanceRequest.php
│   └── Resources/
│       ├── ApplicationStageHistoryResource.php
│       ├── CompanyResource.php
│       ├── DepartmentResource.php
│       ├── HrdTestParticipantResource.php
│       ├── HrdTestScheduleResource.php
│       ├── JobPlacementResource.php
│       ├── JobVacancyResource.php
│       ├── MajorResource.php
│       ├── NotificationResource.php
│       ├── RecruitmentAttendanceResource.php
│       ├── RecruitmentSelectionResource.php
│       ├── SelectionStageSummaryResource.php
│       ├── SelectOptionResource.php
│       ├── StudentAlumniResource.php
│       ├── StudentJobApplicationResource.php
│       ├── StudentMyProfileResource.php
│       ├── StudentPortfolioResource.php
│       ├── StudentResource.php
│       ├── TracerStudyResource.php
│       └── UserResource.php
├── Mail/
│   └── ResetPasswordMail.php                          # Mailable template for password reset link
├── Models/
│   ├── AccessMenu.php                                 # Role-to-menu permission mapping
│   ├── ActivityLog.php                                # User action audit logging
│   ├── ApplicationStageHistory.php                    # Audit trail of applicant stage progression
│   ├── Company.php                                    # Partner corporate entities
│   ├── Department.php                                 # Vocational school departments
│   ├── JobApplication.php                             # Student/alumni job applications (hasOne SelectionResult)
│   ├── JobPlacement.php                               # Accepted student work placement records
│   ├── JobVacancy.php                                 # Job openings posted by companies/BKI
│   ├── Major.php                                      # Vocational majors under departments
│   ├── Menu.php                                       # System navigation menus
│   ├── Notification.php                               # In-app notifications
│   ├── RecruitmentAttendance.php                      # Test event attendance, coordinates, QR verification
│   ├── SelectionResult.php                            # HRD selection score per job application
│   ├── SelectionStage.php                             # Recruitment pipeline stages
│   ├── StandardType.php                               # Dynamic lookup options/constants
│   ├── StandardTypeCategory.php                       # Grouping categories for standard types
│   ├── StudentAlumni.php                              # Student/alumni profile & academic data
│   ├── StudentPortfolio.php                           # Student certificates, achievements, projects
│   ├── TracerStudy.php                                # Graduate employment tracking survey data
│   └── User.php                                       # Application users with role association
├── Services/
│   ├── AdminReportService.php                         # Agregasi data laporan admin, metrik, filter tanggal, dan statistik
│   ├── AuthService.php                                # Authentication credential validation & token issuance
│   ├── CompanyService.php                             # Corporate partner CRUD, filtering, logo upload
│   ├── DepartmentService.php                          # Department master data CRUD & status toggle
│   ├── JobPlacementService.php                        # Job placement CRUD, filtering, audit trail, options, metrics
│   ├── JobVacancyService.php                          # Vacancy business rules, filters, company checks
│   ├── MailService.php                                # Generic email notification dispatch
│   ├── MajorService.php                               # Vocational major CRUD, department relations
│   ├── NotificationService.php                        # In-app notification creation, broadcast via Reverb, read flags
│   ├── PasswordResetService.php                       # Password reset flow end-to-end (broker token, mail dispatch)
│   ├── RecruitmentAttendanceService.php               # Validasi presensi, status update, agregasi counter tahapan
│   ├── RecruitmentSelectionService.php                # [ADMIN] Seleksi rekrutmen view-only: summary, paginasi, filter
│   ├── ResponseService.php                            # Standard JSON response building
│   ├── StandardTypeService.php                        # Generic select options per category + class→major resolution
│   ├── StudentAlumniService.php                       # Alumni CRUD, upgrade siswa→alumni, sync users, soft delete
│   ├── StudentJobApplicationService.php               # Siswa/alumni lamaran saya queries & stage histories
│   ├── StudentJobVacancyService.php                   # Siswa/alumni lowongan kerja discovery & apply
│   ├── StudentPortfolioService.php                    # Self-service portfolio profile & documents
│   ├── StudentService.php                             # Siswa aktif CRUD, filtering, form options, portofolio berkas
│   ├── TestScheduleService.php                        # HRD agenda & jadwal tes, alokasi pelamar lolos berkas, reminder
│   ├── TracerStudyService.php                         # Alumni career survey, metrics, conditional null resets
│   └── UserService.php                                # Superadmin user management & password overrides
└── Support/
    └── SocialMedia.php                                # Normalisasi & build URL platform sosial media
```

## 4. Key Database Entities and Relations

- **`users`**: Base authentication table (`id`, `full_name`, `email`, `phone`, `password`, `role`, `is_active`). Roles: `superadmin`, `admin`, `hrd`, `siswa`, `alumni`.
- **`departments`**: Vocational departments (`id`, `code`, `name`, `description`, `is_active`). HasMany `majors`.
- **`majors`**: Vocational majors (`id`, `department_id`, `code`, `name`, `description`, `is_active`). BelongsTo `departments`, HasMany `student_alumni`.
- **`student_alumni`**: Extended profile linked to `users.id`. Contains NIS, NISN, graduation year, major ID (`majors.id`), class ID, address, CV file path.
- **`job_vacancies`**: Job openings posted by companies or BKI. Contains title, description, requirements, start/end dates, quota, status (`draft`, `published`, `closed`).
- **`job_applications`**: Junction between `job_vacancies.id` and `student_alumni.id`. Tracks status (`pending`, `in_review`, `accepted`, `rejected`). HasOne `selection_results`.
- **`selection_results`**: HRD selection score per `job_applications.id` (`job_application_id` FK cascadeOnDelete). Columns: `admin_selection_status` enum(`lolos`,`tidak_lolos`), `psychotest_score`, `interview_score`, `mcu_score`, `final_score`, `decision` enum(`diterima`,`tidak_diterima`,`cadangan`,`pending`), `status` enum(`draft`,`published`), `letter_path`, `notes`.
- **`selection_stages` & `application_stage_histories`**: Granular tracking of test stages (administrative, psychotest, technical interview, medical) with `minimum_score`.
- **`recruitment_attendances`**: Presensi peserta tahapan seleksi dan workflow validasi admin (`validation_status`: pending/verified/rejected, `validated_by`, `validated_at`, `notes`, `system_action`).
- **`job_placements`**: Records work placement of students/alumni (`student_alumni_id`, `company_id`, `job_application_id`, `placement_status_id`, `accepted_date`, `start_date`, `evaluations` JSON, `notes`).
- **`tracer_studies`**: Linked to `student_alumni.id`, using enum `career_status` (`bekerja`, `wirausaha`, `lanjut_studi`, `mencari_pekerjaan`), with extended columns: `accepted_date`, `job_location`, `company_sector`, and conditional attributes per status.
- **`student_portfolios`**: Portfolio attachments for students and alumni (`student_alumni_id`, `category_id`, `title`, `description`, `file_path`, `original_filename`). Tracks uploaded storage path and user-facing original filename. Supports soft deletes.
- **`notifications`**: In-app notifications with integer primary key `id`, polymorphic `notifiable`, type, data payload, read status timestamp. Broadcast via Reverb.
- **`standard_types` & `standard_type_categories`**: Dynamic lookup options/constants (`class`, `employment_status`, `portfolio_type`, `company_industry`, etc.).
- **`menus` & `access_menus`**: System navigation menus and role permission mapping.
- **`activity_logs`**: Audit logging recording user ID, action, model affected, IP address, and changed attributes.
- **`password_reset_tokens`**: Email-keyed storage of SHA-256 hashed reset tokens (`email`, `token`, `created_at`). Expiry 60 minutes configured in `config/auth.php`.

### Password Reset Flow
- `PasswordResetService::sendResetLink()` generates secure broker token and dispatches `App\Mail\ResetPasswordMail` (queued, view `emails/reset-password.blade.php`).
- Reset URL points to the SPA: `{FRONTEND_URL}/reset-password?token=...&email=...` (`FRONTEND_URL` in `.env`, exposed as `config/app.frontend_url`).

## 5. API Endpoints Map

### Public / Auth
- `POST /api/login` — Authenticate user and issue Sanctum token.
- `POST /api/forgot-password` — Send password reset link email (throttled 6/min).
- `POST /api/reset-password` — Reset password using `token`, `email`, `password` (`min:8`, `confirmed`).

### Authenticated (`auth:sanctum`)
- `GET /api/me` — Retrieve current authenticated user profile and roles.
- `POST /api/logout` — Revoke current Sanctum token.
- `GET /api/notification` — List user notifications.
- `GET /api/notification/unread` — Count unread notifications.
- `PATCH /api/notification/{id}/read` — Mark single notification as read.
- `PATCH /api/notification/read-all` — Mark all notifications as read.

### Role: Admin (`role:admin`)
- **Companies:**
  - `GET /api/admin/companies` — List perusahaan mitra + pagination (`per_page`), search, filter (`industry_id`, `is_active`), sort.
  - `GET /api/admin/companies/options` — Dropdown opsi: `industries` (kategori `company_industry`).
  - `GET /api/admin/companies/{company}` — Detail data perusahaan.
  - `POST /api/admin/companies` — Tambah perusahaan baru.
  - `PUT|PATCH /api/admin/companies/{company}` — Update data perusahaan.
  - `DELETE /api/admin/companies/{company}` — Soft delete perusahaan.
  - `PATCH /api/admin/companies/{company}/toggle-active` — Toggle status aktif/non-aktif.
- **Departments & Majors:**
  - `GET /api/admin/departments` — List departemen vokasi + pagination, search.
  - `GET /api/admin/departments/options` — Dropdown opsi departemen.
  - `GET /api/admin/departments/{department}` — Detail departemen.
  - `POST /api/admin/departments` — Tambah departemen baru.
  - `PUT|PATCH /api/admin/departments/{department}` — Update departemen.
  - `DELETE /api/admin/departments/{department}` — Soft delete departemen.
  - `PATCH /api/admin/departments/{department}/toggle-active` — Toggle status departemen.
  - `GET /api/admin/majors` — List jurusan + pagination, search, filter departemen.
  - `GET /api/admin/majors/options` — Dropdown opsi jurusan (`departments`).
  - `GET /api/admin/majors/{major}` — Detail jurusan.
  - `POST /api/admin/majors` — Tambah jurusan baru.
  - `PUT|PATCH /api/admin/majors/{major}` — Update data jurusan.
  - `DELETE /api/admin/majors/{major}` — Soft delete jurusan.
  - `PATCH /api/admin/majors/{major}/toggle-active` — Toggle status jurusan.
- **Job Vacancies (Admin):**
  - `GET /api/admin/job-vacancies` — List lowongan kerja + pagination, search, filter.
  - `GET /api/admin/job-vacancies/options` — Dropdown opsi form lowongan (perusahaan, status, target, tipe kerja).
  - `GET /api/admin/job-vacancies/{jobVacancy}` — Detail lowongan kerja.
  - `POST /api/admin/job-vacancies` — Buat lowongan kerja baru.
  - `PUT|PATCH /api/admin/job-vacancies/{jobVacancy}` — Update lowongan kerja.
  - `DELETE /api/admin/job-vacancies/{jobVacancy}` — Soft delete lowongan kerja.
  - `PATCH /api/admin/job-vacancies/{jobVacancy}/toggle-active` — Toggle status aktif lowongan.
- **Students & Alumni Management:**
  - `GET /api/admin/students` — List data siswa aktif (role `siswa`) + pagination, search, filters, sort.
  - `GET /api/admin/students/options` — Dropdown opsi form siswa.
  - `GET /api/admin/students/{student}` — Detail data siswa.
  - `POST /api/admin/students` — Tambah siswa baru + pembuatan akun user (role `siswa`).
  - `PUT|PATCH /api/admin/students/{student}` — Update data siswa dan akun user terkait.
  - `DELETE /api/admin/students/{student}` — Soft delete data siswa & non-aktifkan user.
  - `POST /api/admin/students/{student}/portfolios` — Upload dokumen portofolio siswa.
  - `DELETE /api/admin/students/{student}/portfolios/{portfolio}` — Hapus dokumen portofolio siswa.
  - `GET /api/admin/alumni` — List alumni + pagination, search, filter, sort.
  - `GET /api/admin/alumni/options` — Dropdown opsi form alumni.
  - `GET /api/admin/alumni/{alumni}` — Detail data alumni.
  - `POST /api/admin/alumni` — Tambah alumni: upgrade akun siswa terdaftar menjadi alumni.
  - `PUT|PATCH /api/admin/alumni/{alumni}` — Update data alumni.
  - `DELETE /api/admin/alumni/{alumni}` — Soft delete alumni & non-aktifkan user.
- **Attendance Validation:**
  - `GET /api/admin/attendances/vacancies` — Opsi filter lowongan kerja yang memiliki tahapan seleksi.
  - `GET /api/admin/attendances/stage-summaries` — Counter agregat jumlah antrean presensi per tahapan seleksi.
  - `GET /api/admin/attendances/queue` — Antrean presensi pelamar menunggu validasi admin (`validation_status = 'pending'`).
  - `GET /api/admin/attendances/history` — Riwayat keputusan validasi presensi pelamar.
  - `PATCH /api/admin/attendances/bulk-validate` — Validasi massal antrean presensi (verifikasi/tolak).
  - `PATCH /api/admin/attendances/{attendance}/validate` — Validasi keputusan presensi perorangan pelamar.
- **Selection & Tracer Studies:**
  - `GET /api/admin/recruitment-selections` — [ADMIN] View-only seleksi rekrutmen: `summary` + paginated applicants.
  - `GET /api/admin/tracer-studies` — List tracer study alumni + pagination, search, filters, sort.
  - `GET /api/admin/tracer-studies/metrics` — Metrik agregat tracer study (`total_alumni`, `bekerja`, `kuliah`, `wirausaha`, `mencari_kerja`).
  - `GET /api/admin/tracer-studies/options` — Dropdown opsi tracer study.
  - `POST /api/admin/tracer-studies/sync` — Sinkronkan alumni penempatan ke tabel tracer study.
  - `GET /api/admin/tracer-studies/{tracerStudy}` — Detail data tracer study alumni.
  - `POST /api/admin/tracer-studies` — Tambah data tracer study alumni.
  - `PUT|PATCH /api/admin/tracer-studies/{tracerStudy}` — Update data tracer study alumni.
  - `DELETE /api/admin/tracer-studies/{tracerStudy}` — Soft delete data tracer study.
- **Reports:**
  - `GET /api/admin/reports/options` — Dropdown opsi filter laporan admin.
  - `GET /api/admin/reports/recruitment` — Laporan rekapitulasi rekrutmen & metrik.
  - `GET /api/admin/reports/attendance` — Laporan rekapitulasi presensi tahapan seleksi.
  - `GET /api/admin/reports/absorption` — Laporan keterserapan alumni per jurusan.
  - `GET /api/admin/reports/tracer-study` — Laporan evaluasi tracer study & retensi kerja.
- **Lookups:**
  - `GET /api/admin/standard-types` — Generic async-select lookup items (?category=&search=&page=&per_page=).

### Role: Superadmin (`role:superadmin`)
- `GET /api/admin/users` — List pengguna aplikasi + pagination, role filter, search.
- `GET /api/admin/users/options` — Dropdown opsi roles.
- `GET /api/admin/users/{user}` — Detail data user.
- `POST /api/admin/users` — Buat user baru.
- `PUT|PATCH /api/admin/users/{user}` — Update user & role.
- `DELETE /api/admin/users/{user}` — Soft delete user.
- `PATCH /api/admin/users/{user}/toggle-active` — Toggle status aktif user.
- `POST /api/admin/users/{user}/reset-password` — Override password user oleh superadmin.

### Role: HRD (`role:hrd`)
- **Job Vacancies:**
  - `GET /api/hrd/job-vacancies/statistics` — Statistik lowongan HRD: `active` dan `draft_closed`.
  - `GET /api/hrd/job-vacancies/options` — Dropdown form lowongan (jurusan, target, tipe kerja, status).
  - `GET /api/hrd/job-vacancies` — List lowongan kerja milik perusahaan HRD + pagination, filters, `effective_status`, `sort`.
  - `POST /api/hrd/job-vacancies` — Buat lowongan baru.
  - `GET /api/hrd/job-vacancies/{jobVacancy}` — Detail lowongan kerja perusahaan.
  - `PUT|PATCH /api/hrd/job-vacancies/{jobVacancy}` — Update lowongan kerja.
  - `DELETE /api/hrd/job-vacancies/{jobVacancy}` — Soft delete lowongan kerja.
  - `PATCH /api/hrd/job-vacancies/{jobVacancy}/toggle-active` — Buka/tutup status aktif lowongan.
- **Test Schedules:**
  - `GET /api/hrd/test-schedules` — List agenda & jadwal tes + pagination, search, filter.
  - `GET /api/hrd/test-schedules/options` — Dropdown opsi lowongan aktif milik HRD.
  - `POST /api/hrd/test-schedules` — Buat agenda tes baru + auto alokasi peserta lolos berkas + init presensi.
  - `GET /api/hrd/test-schedules/{id}` — Detail agenda tes.
  - `PUT|PATCH /api/hrd/test-schedules/{id}` — Update data agenda tes.
  - `DELETE /api/hrd/test-schedules/{id}` — Soft delete agenda tes.
  - `GET /api/hrd/test-schedules/{id}/participants` — List daftar peserta tes & status presensi.
  - `POST /api/hrd/test-schedules/{id}/participants/{participantId}/remind` — Kirim reminder tes ke peserta.
- **Job Placements:**
  - `GET /api/hrd/job-placements` — List penempatan kerja perusahaan HRD + pagination, search, filter.
  - `GET /api/hrd/job-placements/options` — Dropdown opsi penempatan kerja.
  - `GET /api/hrd/job-placements/metrics` — Metrik evaluasi penempatan kerja (total, 3, 6, 12 bulan).
  - `GET /api/hrd/job-placements/{jobPlacement}` — Detail data penempatan kerja.
  - `POST /api/hrd/job-placements` — Buat data penempatan kerja baru.
  - `PUT|PATCH /api/hrd/job-placements/{jobPlacement}` — Update data penempatan kerja.
  - `DELETE /api/hrd/job-placements/{jobPlacement}` — Soft delete data penempatan kerja.
  - `GET /api/hrd/students-alumni` — Async-select options data siswa/alumni untuk penempatan.

### Role: Siswa & Alumni (`role:siswa,alumni`)
- **Self-Service Portfolio (`/api/siswa/*` & `/api/alumni/*`):**
  - `GET /api/siswa/portfolio/profile` | `GET /api/alumni/portfolio/profile` — Get profil dan berkas portofolio.
  - `GET /api/siswa/portfolio/options` | `GET /api/alumni/portfolio/options` — Dropdown form profil portofolio.
  - `PUT /api/siswa/portfolio/profile` | `PUT /api/alumni/portfolio/profile` — Update profil pengguna.
  - `POST /api/siswa/portfolio/upload` | `POST /api/alumni/portfolio/upload` — Upload dokumen portofolio (CV, sertifikat).
  - `DELETE /api/siswa/portfolio/{portfolio}` | `DELETE /api/alumni/portfolio/{portfolio}` — Hapus dokumen portofolio.
- **Job Vacancy Discovery & Apply:**
  - `GET /api/job-vacancies` — List lowongan kerja tersedia untuk siswa/alumni.
  - `GET /api/job-vacancies/options` — Dropdown opsi filter lowongan kerja.
  - `GET /api/job-vacancies/{jobVacancy}` — Detail data lowongan kerja.
  - `POST /api/job-vacancies/{jobVacancy}/apply` — Kirim lamaran pekerjaan.
- **My Applications:**
  - `GET /api/my-applications` — List riwayat lamaran yang dikirim pengguna yang login.
  - `GET /api/my-applications/{id}` — Detail lamaran dan riwayat progres tahapan seleksi.
- **Tracer Study (Khusus Alumni, `/api/alumni/*`):**
  - `GET /api/alumni/tracer-study` — Ambil data isian survey tracer study alumni.
  - `POST /api/alumni/tracer-study` — Simpan atau update survey tracer study alumni.

### Select mode (`for_select=1`) on index endpoints

`GET /api/admin/companies`, `/api/admin/students`, `/api/admin/majors`, `/api/admin/departments` accept `?for_select=1&search=&page=&per_page=20`.
Instead of the full resource, they return a paginated `SelectOptionResource` collection: `{ value, label, extra? }[]` plus Laravel `meta` (`current_page`, `last_page`, `total`).

- `value` is always the `encrypt()`-ed id string. Submits process through the `DecryptRequest` middleware. Never compare values across responses since encryption uses a random IV.
- `label` is pre-formatted server-side (`nis - name (class)`, `name (code)`).
- `extra` carries id-like keys encrypted via `encrypt_recursive()` plus raw display fields.
- `/api/admin/students` also accepts `eligible=1` (active `siswa` accounts with `graduation_year` null) for the alumni upgrade picker.
- Query parameters are validated by `SelectOptionsRequest` (`search` max 100, `per_page` 1-100); `StandardTypeOptionsRequest` additionally requires an existing `standard_type_categories.code`.

## 6. Response and Error Envelope Standards

All controller responses are formatted via `App\Services\ResponseService`.

Successful JSON response:
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": { }
}
```

Validation failure (FormRequest 422 standard):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Validation error message"]
  }
}
```
