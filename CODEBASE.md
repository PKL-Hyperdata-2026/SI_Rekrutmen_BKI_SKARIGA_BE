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
│   │   │   └── JobVacancyController.php       # Job vacancies CRUD & publishing
│   │   ├── Auth/
│   │   │   └── AuthController.php             # Login, logout, me endpoint
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
│   ├── JobVacancyService.php                  # Vacancy business rules, filters, company checks
│   ├── StudentAlumniService.php               # Student & alumni profile management
│   ├── NotificationService.php                # Notification creation, broadcast, read flags
│   ├── ResponseService.php                    # Standard JSON response building
│   └── MailService.php                        # Email notification dispatch
├── Events/                                    # Domain events (application submitted, stage updated)
├── Mail/                                      # Mailable templates
├── Traits/                                    # Shared traits (Auditable, HasStandardType)
└── Support/                                   # Custom helpers & utilities
```

## 4. Key Database Entities and Relations

- **`users`**: Base authentication table (`id`, `name`, `email`, `password`, `role`). Roles: `admin`, `hrd`, `siswa`, `alumni`.
- **`student_alumni`**: Extended profile linked to `users.id`. Contains NISN, graduation year, major ID (`majors.id`), address, CV file path.
- **`job_vacancies`**: Job postings linked to `companies.id`. Contains title, description, requirements, start/end dates, quota, status (`draft`, `published`, `closed`).
- **`job_applications`**: Junction between `job_vacancies.id` and `student_alumni.id`. Tracks overall status (`pending`, `in_review`, `accepted`, `rejected`).
- **`selection_stages` & `application_stage_histories`**: Granular tracking of test stages (administrative, psychotest, technical interview, medical).
- **`tracer_studies`**: Alumni tracer data linked to `student_alumni.id` (employment status, company name, salary range, field alignment).
- **`activity_logs`**: Security & audit logs recording user ID, action, model affected, IP address, and changed attributes.

## 5. API Endpoints Map

### Public / Auth
- `POST /api/login` — Authenticate user and issue Sanctum token.

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
- `/api/hrd/*` (`role:hrd`) — Company profile, vacancy management, candidate selection pipeline.
- `/api/alumni/*` (`role:alumni`) — Alumni job applications, portfolio updates, tracer study submissions.

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
