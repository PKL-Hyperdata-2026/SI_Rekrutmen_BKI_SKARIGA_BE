 # Backend Agent Instructions (Laravel 13 API)

## Project Context
Decoupled REST API for SI Rekrutmen BKI SKARIGA.
Laravel 13, PHP 8.2+, PostgreSQL, Laravel Sanctum authentication. Timezone: Asia/Jakarta.

## Documentation Map
- `CODEBASE.md` — deep technical reference: architecture, DB schema, models, services, API routes. **Read at session start.**
- `../AGENTS.md` — root orchestrator & shared API contract.
- `routes/api.php` — API route declarations.

## Commands Cheatsheet
```bash
composer install                           # Install PHP dependencies
php artisan serve                          # Start local dev server
php artisan test                           # Run test suite
php artisan migrate                        # Run pending database migrations
php artisan db:seed                        # Seed initial master data
php artisan make:request StoreXRequest     # Generate Form Request validator
php artisan make:resource XResource        # Generate API JsonResource transformer
php artisan make:model X -m                # Generate Model with migration
```

## Critical Rules (READ FIRST)
1. NEVER modify existing migration files. Always create new migrations for schema modifications.
2. NEVER write business logic or complex database queries inside Controllers. Delegate to `app/Services/`.
3. NEVER perform inline validation with `$request->validate()` in Controllers. Always use Form Requests (`app/Http/Requests`).
4. NEVER return Eloquent Models or raw query builder results directly from Controllers. Always transform data using API Resources (`app/Http/Resources`).
5. ALWAYS enforce strict typing (`declare(strict_types=1);`) in every PHP file. Use type hints for all parameters and return types.
6. NEVER edit or commit `.env` files.
7. NEVER edit files inside `vendor/`.
8. Update `CODEBASE.md` whenever models, services, schema, or routes change.

## Non-Default Conventions (Things You'd Get Wrong)
- Controller responsibilities: Controller acts only as HTTP dispatcher. It accepts the FormRequest, calls the corresponding method in `app/Services/`, and returns the response using `ResponseService` or JsonResource.
- Response format: Use standard response helper or `ResponseService` (`success_response($data, $message, $code)` / `error_response($message, $code, $errors)`).
- Model mass assignment: Fillable attributes must be explicitly defined in `$fillable`. Do not use `$guarded = []`.
- Database dates: Timestamps use PostgreSQL `timestamp with time zone` or default Laravel timestamps. Format outputs in UTC / Asia/Jakarta ISO string inside API Resources.
- Role checks: Route middleware uses `role:admin`, `role:hrd`, `role:siswa,alumni`, or `role:alumni` defined in `routes/api.php`.

## Git Workflow
- Working branch: `development`.
- NEVER commit unless explicitly requested by the user.
- Commit format: Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`).
- Always verify tests pass before marking work complete.

## Definition of Done
A backend task is done when:
1. Feature logic is implemented in `app/Services/` and isolated from controllers.
2. Form Request validates all incoming parameters with descriptive error messages.
3. API Resource structures the output matching the frontend contract.
4. `php artisan test` passes with zero failures.
5. `CODEBASE.md` is updated if new models, tables, services, or endpoints were introduced.
