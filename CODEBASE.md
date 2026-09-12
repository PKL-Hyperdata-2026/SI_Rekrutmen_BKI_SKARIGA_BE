# Backend Codebase Reference (Laravel 13 API)

Deep, factual reference for AI agents and developers. **Last verified: 2026-09-11.**
If you modify code that alters any architecture, models, routes, or services documented here, update this file in the same change.
Operational instructions & boundaries: [`AGENTS.md`](./AGENTS.md).

> **Staleness rule:** If this file is >4 weeks old, verify schema and routes against `database/migrations/` and `routes/api.php` before trusting it.

## 1. Stack

| Layer | Technology | Details |
|---|---|---|
| Framework | Laravel 13 | PHP 8.3+, Strict Types enabled |
| Database | PostgreSQL | Relational DB with foreign keys and indexes |
| Authentication | Laravel Sanctum | Stateful/Bearer token API authentication |
| Pattern | Service-Repository / Action | Thin Controllers, Fat Services, API Resources |

## 2. Architecture (How Things Connect)

```text
HTTP Request (Frontend Client)
  → routes/api.php (Route declaration + middleware: auth:sanctum, role:X)
  → app/Http/Middleware/ (Authenticate, CheckRole, RBAC, DecryptRequest)
  → app/Http/Controllers/ (Thin HTTP dispatcher)
      → FormRequest (app/Http/Requests/ - input validation & authorization)
      → Service Layer (app/Services/ - business rules, DB transactions, external integrations)
          → Eloquent Models (app/Models/ - query database, relations, scopes)
          → PostgreSQL Database
      → API JsonResource (app/Http/Resources/ - formatting & filtering response fields)
      → ResponseService / success_response() (wraps into standard JSON envelope)
  → HTTP JSON Response to Client
```

## 3. Project Structure

```text
backend/app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── CompanyController.php               # Admin CRUD perusahaan mitra & options
│   │   │   ├── DepartmentController.php            # Admin CRUD departemen vokasi & toggle active
│   │   │   ├── JobPlacementController.php          # HRD CRUD data penempatan kerja
│   │   │   ├── JobVacancyController.php            # Admin CRUD & toggle active lowongan kerja
│   │   │   ├── MajorController.php                 # Admin CRUD jurusan & toggle active
│   │   │   ├── PortfolioController.php             # Self-service E-Portfolio (role: siswa & alumni)
│   │   │   ├── StandardTypeController.php          # Generic async-select options (?category=&search=&per_page=)
│   │   │   ├── StudentAlumniController.php         # Admin CRUD data alumni (upgrade akun siswa)
│   │   │   ├── StudentController.php               # Admin CRUD data siswa kelas 12 aktif & portfolio
│   │   │   ├── StudentJobApplicationController.php # Siswa/alumni daftar & detail lamaran saya
│   │   │   ├── TracerStudyController.php      # Tracer study submission & detail (role: alumni)
│   │   │   ├── StandardTypeController.php     # Generic async-select options (?category=&search=&per_page=)
│   │   │   └── Hrd/
│   │   │       └── TestScheduleController.php # HRD CRUD agenda & jadwal tes + list peserta + reminder
│   │   │   ├── StudentJobVacancyController.php     # Siswa/alumni eksplorasi lowongan kerja
│   │   │   ├── TracerStudyController.php           # Tracer study submission & detail (role: alumni)
│   │   │   ├── UserController.php                  # Superadmin user management & password reset
│   │   │   └── Hrd/
│   │   │       └── JobVacancyController.php        # HRD CRUD lowongan kerja perusahaan
│   │   ├── Auth/
│   │   │   └── AuthController.php                  # Login, logout, me endpoint + forgot/reset password
│   │   └── NotificationController.php              # Notification listing & read status
│   ├── Requests/                                   # FormRequest classes for validation
│   │   ├── SelectOptionsRequest.php                # Shared index select params (search, page, per_page, for_select, eligible)
│   │   └── StandardTypeOptionsRequest.php          # category (required, must exist) + select params
│   ├── Resources/                                  # JsonResource transformers
│   │   └── SelectOptionResource.php                # {value (encrypted id), label, extra} for async selects
│   └── Middleware/                                 # Role checks (RBAC), DecryptRequest, filters
├── Models/                                         # Eloquent ORM entity models
│   ├── AccessMenu.php                              # Role-to-menu permission mapping
│   ├── ActivityLog.php                             # User action audit logging
│   ├── ApplicationStageHistory.php                 # Audit trail of applicant stage progression
│   ├── Company.php                                 # Partner corporate entities
│   ├── Department.php                              # Vocational school departments
│   ├── JobApplication.php                          # Student/alumni job applications (hasOne SelectionResult)
│   ├── JobPlacement.php                            # Accepted student work placement records
│   ├── JobVacancy.php                              # Job openings posted by companies/BKI
│   ├── Major.php                                   # Vocational majors under departments
│   ├── Menu.php                                    # System navigation menus
│   ├── Notification.php                            # In-app notifications
│   ├── RecruitmentAttendance.php                   # Test event attendance, coordinates, QR verification
│   ├── SelectionResult.php                         # HRD input hasil seleksi per lamaran
│   ├── SelectionStage.php                          # Recruitment pipeline stages
│   ├── StandardType.php                            # Dynamic lookup options/constants
│   ├── StandardTypeCategory.php                    # Grouping categories for standard types
│   ├── StudentAlumni.php                           # Student/alumni profile & academic data
│   ├── StudentPortfolio.php                        # Student certificates, achievements, projects
│   ├── TracerStudy.php                             # Graduate employment tracking survey data
│   └── User.php                                    # Application users with role association
├── Services/
│   ├── JobPlacementService.php                # Job placement CRUD, filtering, audit trail, options
│   ├── JobVacancyService.php                  # Vacancy business rules, filters, company checks
│   ├── PasswordResetService.php               # Password reset flow end-to-end (broker token, mail, response mapping)
│   ├── StudentAlumniService.php               # Alumni CRUD (list/filter, upgrade siswa→alumni, sync users, soft delete)
│   ├── StudentPortfolioService.php            # Self-service E-Portfolio (profile, options, upload/delete dokumen) — shared siswa & alumni
│   ├── StudentJobApplicationService.php       # Siswa/alumni lamaran saya queries & stage histories
│   ├── StudentService.php                     # Siswa aktif CRUD, filtering, form options, portofolio berkas
│   ├── TracerStudyService.php                 # Alumni career status survey, conditional null resets, DB transactions
│   ├── NotificationService.php                # Notification creation, broadcast, read flags
│   ├── StandardTypeService.php                # Generic select options per category + class→major fuzzy resolution
│   ├── TestScheduleService.php                # HRD agenda & jadwal tes, alokasi pelamar lolos berkas, reminder, options
│   ├── ResponseService.php                    # Standard JSON response building
│   └── MailService.php                        # Email notification dispatch
├── Events/                                    # Domain events (application submitted, stage updated)
├── Mail/                                      # Mailable templates
├── Traits/                                    # Shared traits (Auditable, HasStandardType)
│   ├── CompanyService.php                          # Corporate partner CRUD, filtering, logo upload
│   ├── DepartmentService.php                       # Department master data CRUD & status toggle
│   ├── JobPlacementService.php                     # Job placement CRUD, filtering, audit trail, options
│   ├── JobVacancyService.php                       # Vacancy business rules, filters, company checks
│   ├── MailService.php                             # Email notification dispatch
│   ├── MajorService.php                            # Vocational major CRUD, department relations
│   ├── NotificationService.php                     # Notification creation, broadcast, read flags
│   ├── PasswordResetService.php                    # Password reset flow end-to-end
│   ├── ResponseService.php                         # Standard JSON response building
│   ├── StandardTypeService.php                     # Generic select options per category + class to major resolution
│   ├── StudentAlumniService.php                    # Alumni CRUD, upgrade siswa to alumni, sync users
│   ├── StudentJobApplicationService.php            # Siswa/alumni lamaran saya queries & stage histories
│   ├── StudentJobVacancyService.php                # Siswa/alumni vacancy discovery queries & filters
│   ├── StudentPortfolioService.php                 # Self-service portfolio profile & documents
│   ├── StudentService.php                          # Siswa aktif CRUD, filtering, form options, portofolio berkas
│   ├── TracerStudyService.php                      # Alumni career survey, conditional null resets
│   └── UserService.php                             # Superadmin user management & password overrides
├── Events/                                         # Domain events (application submitted, stage updated)
├── Mail/                                           # Mailable templates
├── Traits/                                         # Shared traits (Auditable, HasStandardType)
└── Support/
    └── SocialMedia.php                             # Normalisasi & build URL platform sosial media
```

## 4. Key Database Entities and Relations

- **`users`**: Base authentication table (`id`, `full_name`, `email`, `phone`, `password`, `role`, `is_active`). Roles: `superadmin`, `admin`, `hrd`, `siswa`, `alumni`.
- **`departments`**: Vocational departments (`id`, `code`, `name`, `description`, `is_active`). HasMany `majors`.
- **`majors`**: Vocational majors (`id`, `department_id`, `code`, `name`, `description`, `is_active`). BelongsTo `departments`, HasMany `student_alumni`.
- **`student_alumni`**: Extended profile linked to `users.id`. Contains NISN, graduation year, major ID (`majors.id`), address, CV file path.
- **`job_vacancies`**: Job postings linked to `companies.id`. Contains title, description, requirements, start/end dates, quota, status (`draft`, `published`, `closed`).
- **`job_applications`**: Junction between `job_vacancies.id` and `student_alumni.id`. Tracks status (`pending`, `in_review`, `accepted`, `rejected`). HasOne `selection_results`.
- **`selection_results`**: HRD selection score per `job_applications.id` (`job_application_id` FK cascadeOnDelete). Columns: `admin_selection_status` enum(`lolos`,`tidak_lolos`), `psychotest_score`, `interview_score`, `mcu_score`, `final_score`, `decision` enum(`diterima`,`tidak_diterima`,`cadangan`,`pending`), `status` enum(`draft`,`published`), `notes`.
- **`selection_stages` & `application_stage_histories`**: Tracking test stages (administrative, psychotest, technical interview, medical).
- **`recruitment_attendances`**: Attendance records linked to `application_stage_histories.id` (`attendance_status_id`, `qr_code_token`, `attended_at`, `latitude`, `longitude`, `photo_selfie_path`).
- **`job_placements`**: Records work placement of students/alumni (`student_alumni_id`, `company_id`, `job_application_id`, `placement_status_id`, `accepted_date`, `start_date`, `notes`).
- **`tracer_studies`**: Linked to `student_alumni.id`, using enum `career_status` (`bekerja`, `wirausaha`, `lanjut_studi`, `mencari_pekerjaan`), with conditional attributes per status.
- **`student_portfolios`**: Portfolio attachments for students and alumni (`student_alumni_id`, `category_id`, `title`, `description`, `file_path`, `original_filename`). Tracks uploaded storage path and user-facing original filename. Supports soft deletes.
- **`activity_logs`**: Audit logging recording user ID, action, model affected, IP address, and changed attributes.
- **`password_reset_tokens`**: Email-keyed storage of SHA-256 hashed reset tokens (`email`, `token`, `created_at`). Expiry 60 minutes configured in `config/auth.php`.

### Password Reset Flow
- `AppServiceProvider::boot()` overrides `Password::sendMessageUsing()` so reset links are delivered via `App\Mail\ResetPasswordMail` (queued, view `emails/reset-password.blade.php`).
- Reset URL points to the SPA: `{FRONTEND_URL}/reset-password?token=...&email=...` (`FRONTEND_URL` in `.env`, exposed as `config/app.frontend_url`).

## 5. API Endpoints Map

### Public / Auth
- `POST /api/login`, Authenticate user and issue Sanctum token.
- `POST /api/forgot-password`, Send password reset link email (throttled 6/min).
- `POST /api/reset-password`, Reset password using `token`, `email`, `password` (`min:8`, `confirmed`).

### Authenticated (`auth:sanctum`)
- `GET /api/me`, Retrieve current authenticated user profile and roles.
- `POST /api/logout`, Revoke current Sanctum token.
- `GET /api/notification`, List user notifications.
- `GET /api/notification/unread`, Count unread notifications.
- `PATCH /api/notification/{id}/read`, Mark single notification as read.
- `PATCH /api/notification/read-all`, Mark all notifications as read.

### Role-Protected Routes
- `/api/admin/*` (`role:admin`) — User management, school masters, verification, analytics.
  - `GET /api/admin/students` — List data siswa aktif (role `siswa`) + pagination (`per_page`), search (nama/NIS/email/phone/jurusan/perusahaan), filter (`major_id`, `class_id`, `employment_status_id`, `graduation_year`, `is_active`), sort (`sort_by`, `sort_dir`).
  - `GET /api/admin/students/options` — Dropdown: `majors`, `classes`, `employment_statuses`, `portfolio_types`, `companies`, `graduation_years`.
  - `GET /api/admin/students/{student}` — Detail data siswa (dengan relasi user/major/class/employment_status/current_company/portfolios.category).
  - `POST /api/admin/students` — Tambah siswa baru + pembuatan akun user (role `siswa`, is_active `true`). Dalam `DB::transaction()`.
  - `PUT|PATCH /api/admin/students/{student}` — Update data siswa dan akun user terkait. Dalam `DB::transaction()`.
  - `DELETE /api/admin/students/{student}` — Soft delete data siswa & akun user + set `users.is_active = false`. Dalam `DB::transaction()`.
  - `POST /api/admin/students/{student}/portfolios` — Upload dokumen portofolio siswa (CV, Sertifikat PKL, dll).
  - `DELETE /api/admin/students/{student}/portfolios/{portfolio}` — Hapus dokumen portofolio siswa.
  - `GET /api/admin/alumni` — List alumni (`graduation_year` terisi) + pagination (`per_page`), search (nama/NIS/perusahaan), filter (`graduation_year`, `major_id`, `employment_status_id`, `current_company_id`), sort (`sort_by`, `sort_dir`).
  - `GET /api/admin/alumni/options` — Dropdown: `majors`, `classes`, `employment_statuses`, `companies`, `graduation_years`.
  - `GET /api/admin/alumni/{alumni}` — Detail alumni (dengan relasi user/major/class/employment_status/current_company).
  - `POST /api/admin/alumni` — Tambah alumni: upgrade akun siswa (`user_id` wajib, role `siswa`), isi data alumni, set `users.role = alumni`. Tanpa pembuatan akun baru / email. Dalam `DB::transaction()`.
  - `PUT|PATCH /api/admin/alumni/{alumni}` — Update data alumni; sinkron `users.full_name`/`phone`; role mengikuti `graduation_year` (terisi → `alumni`, kosong → `siswa`). Dalam `DB::transaction()`.
  - `DELETE /api/admin/alumni/{alumni}` — Soft delete + `deleted_by` + set `users.is_active = false`. Dalam `DB::transaction()`.
  - `GET /api/admin/companies` — List perusahaan mitra + pagination (`per_page`), search (nama/email/PIC/phone/industri), filter (`industry_id`, `is_active`), sort (`sort_by`, `sort_dir`).
  - `GET /api/admin/companies/options` — Dropdown opsi: `industries` (kategori `company_industry`).
  - `GET /api/admin/companies/{company}` — Detail perusahaan (dengan relasi industry/createdBy/updatedBy).
  - `POST /api/admin/companies` — Tambah perusahaan baru (nama wajib, email/website valid, nomor HP Indonesia, `is_active` default true).
  - `PUT|PATCH /api/admin/companies/{company}` — Update data perusahaan.
  - `DELETE /api/admin/companies/{company}` — Soft delete + `deleted_by`.
  - `PATCH /api/admin/companies/{company}/toggle-active` — Toggle status aktif/non-aktif (status MoU BKK).
- `/api/hrd/*` (`role:hrd`) — Company profile, vacancy management, candidate selection pipeline, job placements.
  - `GET /api/hrd/job-placements` — List penempatan kerja perusahaan HRD + pagination (`per_page`), search (nama/NIS/notes), filter (`student_alumni_id`, `placement_status_id`, `job_application_id`, `year`), sort (`sort_by`, `sort_dir`).
  - `GET /api/hrd/job-placements/options` — Dropdown opsi: `companies`, `placement_statuses`, `students_alumni`.
  - `GET /api/hrd/job-placements/metrics` — Metrik evaluasi penempatan kerja (total, 3 bulan, 6 bulan, 12 bulan).
  - `GET /api/hrd/job-placements/{jobPlacement}` — Detail penempatan kerja (dengan relasi studentAlumni, company, placementStatus, jobApplication).
  - `POST /api/hrd/job-placements` — Tambah penempatan kerja. Dalam `DB::transaction()`.
  - `PUT|PATCH /api/hrd/job-placements/{jobPlacement}` — Update data penempatan kerja. Dalam `DB::transaction()`.
  - `DELETE /api/hrd/job-placements/{jobPlacement}` — Soft delete + `deleted_by`. Dalam `DB::transaction()`.
  - `GET /api/hrd/test-schedules` — List agenda & jadwal tes perusahaan HRD + pagination (`per_page`), search (nama/lokasi/posisi), filter (`job_vacancy_id`, `session_status`), sort (`sort_by`, `sort_dir`).
  - `GET /api/hrd/test-schedules/options` — Dropdown form jadwal tes: daftar lowongan kerja aktif milik perusahaan HRD.
  - `POST /api/hrd/test-schedules` — Buat agenda tes baru + auto alokasikan peserta (hanya pelamar yang lolos berkas pada lowongan tersebut) + inisialisasi presensi + kirim notifikasi in-app otomatis jika dicentang. Dalam `DB::transaction()`.
  - `GET /api/hrd/test-schedules/{id}` — Detail agenda tes (dengan relasi lowongan & total peserta).
  - `PUT|PATCH /api/hrd/test-schedules/{id}` — Update data agenda tes + kirim notifikasi perubahan jika dicentang. Dalam `DB::transaction()`.
  - `DELETE /api/hrd/test-schedules/{id}` — Soft delete agenda tes + `deleted_by`. Dalam `DB::transaction()`.
  - `GET /api/hrd/test-schedules/{id}/participants` — List daftar peserta pada agenda tes terkait (nama, NIS, NISN, email, phone, status presensi).
  - `POST /api/hrd/test-schedules/{id}/participants/{participantId}/remind` — Kirim notifikasi pengingat tes (*Kirim Reminder*) ke peserta.
- `/api/siswa/*` (`role:siswa`) — Self-service E-Portfolio siswa (profil + dokumen).
  - `GET /api/siswa/portfolio/profile` — Profil + portofolio siswa yang login.
  - `GET /api/siswa/portfolio/options` — Dropdown form: `majors`, `classes`, `employment_statuses`, `portfolio_types`, `graduation_years`.
  - `PUT /api/siswa/portfolio/profile` — Update profil siswa (NIS, nama, email, telepon, kelas, jurusan, tahun lulus, sosial media).
  - `POST /api/siswa/portfolio/upload` — Upload dokumen portofolio (CV, sertifikat, dll).
  - `DELETE /api/siswa/portfolio/{portfolio}` — Hapus dokumen portofolio siswa.
- `/api/alumni/*` (`role:alumni`) — Self-service E-Portfolio alumni (profil + dokumen + status karir).
  - `GET /api/alumni/portfolio/profile` — Profil + portofolio alumni yang login (termasuk `employmentStatusId`).
  - `GET /api/alumni/portfolio/options` — Dropdown form (sama dengan siswa, termasuk `employment_statuses`).
  - `PUT /api/alumni/portfolio/profile` — Update profil alumni (plus `employment_status_id`).
  - `POST /api/alumni/portfolio/upload` — Upload dokumen portofolio alumni.
  - `DELETE /api/alumni/portfolio/{portfolio}` — Hapus dokumen portofolio alumni.
  - `GET /api/alumni/tracer-study` — Ambil data pengisian tracer study alumni yang sedang login.
  - `POST /api/alumni/tracer-study` — Submit atau update data tracer study alumni.
- `/api/my-applications` (`role:siswa,alumni`) — List lamaran saya siswa/alumni (pagination & filter `status_id`).
- `/api/my-applications/{id}` (`role:siswa,alumni`) — Detail spesifik lamaran siswa beserta timeline tahapan seleksi (`stage_histories`).
- `/api/alumni/*` (`role:alumni`) — Alumni job applications, portfolio updates, tracer study submissions.
- `GET /api/admin/standard-types?category=&search=&page=&per_page=` (`role:admin`) — Generic async-select options for standard-type lookups (`class`, `employment_status`, `portfolio_type`, `company_industry`, ...). Paginated (`per_page` default 20, max 100). Category `class` items carry `extra.resolvedMajorId`/`resolvedMajorName` (server-side port of the FE `resolveMajorByClass` fuzzy match).
- `GET /api/hrd/students-alumni?search=&page=&per_page=` (`role:hrd`) — Async-select options for active students/alumni ordered by name (used by the HRD placement form).
- `/api/admin/*` (`role:admin`, also accessible by `superadmin`)
  - `GET /api/admin/companies`, List perusahaan mitra + pagination (`per_page`), search, filter (`industry_id`, `is_active`), sort.
  - `GET /api/admin/companies/options`, Dropdown options for companies (`industries`).
  - `GET /api/admin/companies/{company}`, Detail single company.
  - `POST /api/admin/companies`, Create new company.
  - `PUT|PATCH /api/admin/companies/{company}`, Update company data.
  - `DELETE /api/admin/companies/{company}`, Soft delete company.
  - `PATCH /api/admin/companies/{company}/toggle-active`, Toggle active status.
  - `GET /api/admin/departments`, List departments with pagination and search.
  - `GET /api/admin/departments/options`, Dropdown options for departments.
  - `GET /api/admin/departments/{department}`, Detail single department.
  - `POST /api/admin/departments`, Create new department.
  - `PUT|PATCH /api/admin/departments/{department}`, Update department.
  - `DELETE /api/admin/departments/{department}`, Soft delete department.
  - `PATCH /api/admin/departments/{department}/toggle-active`, Toggle department active status.
  - `GET /api/admin/majors`, List majors with pagination, search, and department filter.
  - `GET /api/admin/majors/options`, Dropdown options for majors (`departments`).
  - `GET /api/admin/majors/{major}`, Detail single major.
  - `POST /api/admin/majors`, Create new major.
  - `PUT|PATCH /api/admin/majors/{major}`, Update major.
  - `DELETE /api/admin/majors/{major}`, Soft delete major.
  - `PATCH /api/admin/majors/{major}/toggle-active`, Toggle major active status.
  - `GET /api/admin/job-vacancies`, List job vacancies with pagination and filters.
  - `GET /api/admin/job-vacancies/options`, Dropdown options for job vacancies.
  - `GET /api/admin/job-vacancies/{jobVacancy}`, Detail single job vacancy.
  - `POST /api/admin/job-vacancies`, Create new job vacancy.
  - `PUT|PATCH /api/admin/job-vacancies/{jobVacancy}`, Update job vacancy.
  - `DELETE /api/admin/job-vacancies/{jobVacancy}`, Soft delete job vacancy.
  - `PATCH /api/admin/job-vacancies/{jobVacancy}/toggle-active`, Toggle vacancy active status.
  - `GET /api/admin/students`, List active students with pagination, search, filters, and sort.
  - `GET /api/admin/students/options`, Dropdown options for students.
  - `GET /api/admin/students/{student}`, Detail student with relations.
  - `POST /api/admin/students`, Create new student and user account.
  - `PUT|PATCH /api/admin/students/{student}`, Update student and user profile.
  - `DELETE /api/admin/students/{student}`, Soft delete student and deactivate user.
  - `POST /api/admin/students/{student}/portfolios`, Upload student portfolio document.
  - `DELETE /api/admin/students/{student}/portfolios/{portfolio}`, Remove student portfolio document.
  - `GET /api/admin/alumni`, List alumni with pagination, search, and filters.
  - `GET /api/admin/alumni/options`, Dropdown options for alumni.
  - `GET /api/admin/alumni/{alumni}`, Detail alumni with relations.
  - `POST /api/admin/alumni`, Upgrade student user to alumni role.
  - `PUT|PATCH /api/admin/alumni/{alumni}`, Update alumni profile.
  - `DELETE /api/admin/alumni/{alumni}`, Soft delete alumni and deactivate user.
  - `GET /api/admin/standard-types`, Standard type lookup items (?category=&search=&page=&per_page=).
- `/api/admin/*` (`role:superadmin`)
  - `GET /api/admin/users`, List application users with pagination, role filter, and search.
  - `GET /api/admin/users/options`, Dropdown options for user management (roles).
  - `GET /api/admin/users/{user}`, Detail single user.
  - `POST /api/admin/users`, Create new user.
  - `PUT|PATCH /api/admin/users/{user}`, Update user details and role.
  - `DELETE /api/admin/users/{user}`, Soft delete user.
  - `PATCH /api/admin/users/{user}/toggle-active`, Toggle user active status.
  - `POST /api/admin/users/{user}/reset-password`, Admin password reset override.
- `/api/hrd/*` (`role:hrd`)
  - `GET /api/hrd/job-placements`, List job placements with pagination and search.
  - `GET /api/hrd/job-placements/options`, Dropdown options for job placements.
  - `GET /api/hrd/job-placements/metrics`, Placement evaluation metrics (total, 3/6/12 months).
  - `GET /api/hrd/job-placements/{jobPlacement}`, Detail single job placement.
  - `POST /api/hrd/job-placements`, Create job placement record.
  - `PUT|PATCH /api/hrd/job-placements/{jobPlacement}`, Update job placement.
  - `DELETE /api/hrd/job-placements/{jobPlacement}`, Soft delete job placement.
  - `GET /api/hrd/students-alumni`, Async select options for students/alumni picker.
- `/api/siswa/*` (`role:siswa,alumni`)
  - `GET /api/siswa/portfolio/profile`, Get self student profile and portfolio documents.
  - `GET /api/siswa/portfolio/options`, Dropdown options for student portfolio form.
  - `PUT /api/siswa/portfolio/profile`, Update student profile.
  - `POST /api/siswa/portfolio/upload`, Upload portfolio document.
  - `DELETE /api/siswa/portfolio/{portfolio}`, Delete portfolio document.
- `/api/my-applications` (`role:siswa,alumni`)
  - `GET /api/my-applications`, List applications submitted by current user.
  - `GET /api/my-applications/{id}`, Detail single application with selection stage histories.
- `/api/alumni/*` (`role:alumni`)
  - `GET /api/alumni/portfolio/profile`, Get self alumni profile and portfolio.
  - `GET /api/alumni/portfolio/options`, Dropdown options for alumni portfolio form.
  - `PUT /api/alumni/portfolio/profile`, Update alumni profile.
  - `POST /api/alumni/portfolio/upload`, Upload alumni portfolio document.
  - `DELETE /api/alumni/portfolio/{portfolio}`, Delete alumni portfolio document.
  - `GET /api/alumni/tracer-study`, Retrieve filled tracer study data.
  - `POST /api/alumni/tracer-study`, Submit or update tracer study data.

### Select mode (`for_select=1`) on index endpoints

`GET /api/admin/companies`, `/api/admin/students`, `/api/admin/majors`, `/api/admin/departments` accept `?for_select=1&search=&page=&per_page=20`.
Instead of the full resource, they return a paginated `SelectOptionResource` collection: `{ value, label, extra? }[]` plus Laravel `meta` (`current_page`, `last_page`, `total`).

- `value` is always the `encrypt()`-ed id string. Submits process through the `DecryptRequest` middleware. Never compare values across responses since encryption uses a random IV.
- `label` is pre-formatted server-side (`nis - name (class)`, `name (code)`).
- `extra` carries id-like keys encrypted via `encrypt_recursive()` plus raw display fields.
- `/api/admin/students` also accepts `eligible=1` (active `siswa` accounts with `graduation_year` null) for the alumni upgrade picker.
- Query parameters are validated by `SelectOptionsRequest` (`search` max 100, `per_page` 1-100); `StandardTypeOptionsRequest` additionally requires an existing `standard_type_categories.code`.

## 6. Response and Error Envelope Standards

All responses dispatched from controllers conform to:

```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": { },
  "errors": null
}
```

Error responses format:
```json
{
  "success": false,
  "message": "Validation error",
  "data": null,
  "errors": {
    "email": ["The email field is required."]
  }
}
```
