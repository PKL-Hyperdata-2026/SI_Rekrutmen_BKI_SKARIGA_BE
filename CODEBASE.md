# Backend Codebase Reference (Laravel 13 API)

Deep, factual reference for AI agents and developers. **Last verified: 2026-08-20.**
If you modify code that alters any architecture, models, routes, or services documented here, update this file in the same change.
Operational instructions & boundaries: [`AGENTS.md`](./AGENTS.md).

> **Staleness rule:** If this file is >4 weeks old, verify schema and routes against `database/migrations/` and `routes/api.php` before trusting it.

## 1. Stack

| Layer | Technology | Details |
|---|---|---|
| Framework | Laravel 13 | PHP 8.2+, Strict Types enabled |
| Database | PostgreSQL | Relational DB with foreign keys and indexes |
| Authentication | Laravel Sanctum | Stateful/Bearer token API authentication |
| Pattern | Service-Repository / Action | Thin Controllers, Fat Services, API Resources |

## 2. Architecture (How Things Connect)

```text
HTTP Request (Frontend Client)
  → routes/api.php (Route declaration + middleware: auth:sanctum, role:X)
  → app/Http/Middleware/ (Authenticate, CheckRole)
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
│   │   │   ├── Alumni/
│   │   │   │   └── AlumniPortfolioController.php   # Self-service E-Portfolio alumni (role: alumni)
│   │   │   ├── Student/
│   │   │   │   └── SiswaPortfolioController.php    # Self-service E-Portfolio siswa (role: siswa)
│   │   │   ├── JobPlacementController.php     # Admin CRUD data penempatan kerja
│   │   │   ├── JobVacancyController.php       # Job vacancies CRUD & publishing
│   │   │   ├── StudentAlumniController.php    # Admin CRUD data alumni (upgrade akun siswa)
│   │   │   └── StudentController.php          # Admin CRUD data siswa kelas 12 aktif & portfolio
│   │   ├── Auth/
│   │   │   └── AuthController.php             # Login, logout, me endpoint + forgot/reset password
│   │   └── NotificationController.php         # Notification listing & read status
│   ├── Requests/                              # FormRequest classes for validation
│   ├── Resources/                             # JsonResource transformers
│   └── Middleware/                            # Role checks and custom filters
├── Models/                                    # Eloquent ORM entity models
│   ├── User.php                               # Application users with role association
│   ├── JobVacancy.php                         # Job openings posted by companies/BKI
│   ├── JobApplication.php                     # Student/alumni job applications
│   ├── SelectionStage.php                     # Recruitment pipeline stages (Admin/HRD)
│   ├── ApplicationStageHistory.php            # Audit trail of applicant stage progression
│   ├── JobPlacement.php                       # Accepted student work placement records
│   ├── StudentAlumni.php                      # Student/alumni profile & academic data
│   ├── StudentPortfolio.php                   # Student certificates, achievements, projects
│   ├── TracerStudy.php                        # Graduate employment tracking survey data
│   ├── Major.php                              # School vocational majors (Jurusan)
│   ├── Company.php                            # Partner corporate entities
│   ├── Notification.php                       # In-app notifications
│   ├── ActivityLog.php                        # User action audit logging
│   ├── StandardType.php                       # Dynamic lookup options/constants
│   ├── StandardTypeCategory.php               # Grouping categories for standard types
│   ├── Menu.php                               # System navigation menus
│   └── AccessMenu.php                         # Role-to-menu permission mapping
├── Services/
│   ├── JobPlacementService.php                # Job placement CRUD, filtering, audit trail, options
│   ├── JobVacancyService.php                  # Vacancy business rules, filters, company checks
│   ├── PasswordResetService.php               # Password reset flow end-to-end (broker token, mail, response mapping)
│   ├── StudentAlumniService.php               # Alumni CRUD (list/filter, upgrade siswa→alumni, sync users, soft delete)
│   ├── StudentPortfolioService.php            # Self-service E-Portfolio (profile, options, upload/delete dokumen) — shared siswa & alumni
│   ├── StudentService.php                     # Siswa aktif CRUD, filtering, form options, portofolio berkas
│   ├── NotificationService.php                # Notification creation, broadcast, read flags
│   ├── ResponseService.php                    # Standard JSON response building
│   └── MailService.php                        # Email notification dispatch
├── Events/                                    # Domain events (application submitted, stage updated)
├── Mail/                                      # Mailable templates
├── Traits/                                    # Shared traits (Auditable, HasStandardType)
└── Support/
    └── SocialMedia.php                        # Normalisasi & build URL platform sosial media (backward-compatible)
```

## 4. Key Database Entities and Relations

- **`users`**: Base authentication table (`id`, `name`, `email`, `password`, `role`). Roles: `admin`, `hrd`, `siswa`, `alumni`.
- **`student_alumni`**: Extended profile linked to `users.id`. Contains NISN, graduation year, major ID (`majors.id`), address, CV file path.
- **`job_vacancies`**: Job postings linked to `companies.id`. Contains title, description, requirements, start/end dates, quota, status (`draft`, `published`, `closed`).
- **`job_applications`**: Junction between `job_vacancies.id` and `student_alumni.id`. Tracks overall status (`pending`, `in_review`, `accepted`, `rejected`).
- **`selection_stages` & `application_stage_histories`**: Granular tracking of test stages (administrative, psychotest, technical interview, medical).
- **`job_placements`**: Records work placement of students/alumni (`student_alumni_id`, `company_id`, `job_application_id`, `placement_status_id`, `accepted_date`, `start_date`, `notes`).
- **`tracer_studies`**: Alumni tracer data linked to `student_alumni.id` (employment status, company name, salary range, field alignment).
- **`activity_logs`**: Security & audit logs recording user ID, action, model affected, IP address, and changed attributes.
- **`password_reset_tokens`**: Email-keyed storage of SHA-256 hashed reset tokens (`email`, `token`, `created_at`). Expiry 60 minutes & 60s resend throttle configured in `config/auth.php` (`passwords.users`).

### Password Reset Flow
- `AppServiceProvider::boot()` overrides `Password::sendMessageUsing()` so reset links are delivered via `App\Mail\ResetPasswordMail` (queued, view `emails/reset-password.blade.php`) instead of the default Laravel notification.
- Reset URL points to the SPA: `{FRONTEND_URL}/reset-password?token=...&email=...` (`FRONTEND_URL` in `.env`, exposed as `config/app.frontend_url`).

## 5. API Endpoints Map

### Public / Auth
- `POST /api/login` — Authenticate user and issue Sanctum token.
- `POST /api/forgot-password` — Send password reset link email (throttled 6/min). Always returns success even if email is not registered (anti user-enumeration). Uses Laravel Password broker; token stored hashed in `password_reset_tokens`.
- `POST /api/reset-password` — Reset password using `token`, `email`, `password` (`min:8`, `confirmed`). Invalid/expired token returns 422.

### Authenticated (`auth:sanctum`)
- `GET /api/me` — Retrieve current authenticated user profile and roles.
- `POST /api/logout` — Revoke current Sanctum token.
- `GET /api/job-vacancies` — List active job vacancies with filtering.
- `GET /api/job-vacancies/{id}` — Detail of single job vacancy.
- `GET /api/notification` — List user notifications.
- `GET /api/notification/unread` — Count unread notifications.
- `PATCH /api/notification/{id}/read` — Mark single notification as read.
- `PATCH /api/notification/read-all` — Mark all notifications as read.

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
  - `GET /api/admin/job-placements` — List penempatan kerja + pagination (`per_page`), search (nama/NIS/perusahaan/notes), filter (`student_alumni_id`, `company_id`, `placement_status_id`, `job_application_id`, `year`), sort (`sort_by`, `sort_dir`).
  - `GET /api/admin/job-placements/options` — Dropdown opsi: `companies`, `placement_statuses`, `students_alumni`.
  - `GET /api/admin/job-placements/{jobPlacement}` — Detail penempatan kerja (dengan relasi studentAlumni, company, placementStatus, jobApplication).
  - `POST /api/admin/job-placements` — Tambah penempatan kerja. Dalam `DB::transaction()`.
  - `PUT|PATCH /api/admin/job-placements/{jobPlacement}` — Update data penempatan kerja. Dalam `DB::transaction()`.
  - `DELETE /api/admin/job-placements/{jobPlacement}` — Soft delete + `deleted_by`. Dalam `DB::transaction()`.
- `/api/hrd/*` (`role:hrd`) — Company profile, vacancy management, candidate selection pipeline.
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

## 6. Response and Error Envelope Standards

All responses dispatched from controllers must conform to:

```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": { ... },
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
