# Coding Convention

- **Project:** Rekrutmen BKI SKARIGA
- **Backend:** Laravel 13
- **Frontend:** React + TypeScript + Vite
- **Database:** PostgreSQL

Dokumen ini berisi standar penulisan kode untuk pengembangan Backend (Laravel) dan Frontend (React + TypeScript) pada proyek SI Rekrutmen BKI SKARIGA. Tujuannya adalah untuk menjaga readability, maintainability, mempermudah kolaborasi tim, dan membantu AI Agent dalam memahami codebase.

---

## 1. General Rules

- **Bahasa:** Gunakan Bahasa Inggris untuk penamaan variabel, fungsi, class, dan database. Komentar penjelasan logika (jika panjang) boleh menggunakan Bahasa Indonesia, namun utamakan Bahasa Inggris.
- **Lokasi Dokumen:** Dokumentasi proyek berada di satu tempat, yaitu folder `docs/` di root — bukan di dalam repository BE/FE.

---

## 2. Naming Convention

| Tipe | Format | Contoh |
| :--- | :--- | :--- |
| Variabel & Fungsi (JS/TS/PHP) | `camelCase` | `getUserData()`, `isVerified` |
| Class (PHP/TS) | `PascalCase` | `UserController`, `ApplicantService` |
| Konstanta / Enum | `UPPER_SNAKE_CASE` | `MAX_UPLOAD_SIZE`, `STATUS_PENDING` |
| File Frontend (umum) | `kebab-case` | `login-form.tsx`, `app-sidebar.tsx`, `auth.layout.tsx`, `use-mobile.ts` |
| Nama komponen / hook yang diexport | `PascalCase` | `LoginForm`, `AppSidebar`, `MainLayout` |
| File Backend (Laravel) | `PascalCase.php` | `JobVacancyController.php`, `JobVacancyService.php` |
| Database Table | `snake_case` (Plural) | `users`, `job_vacancies`, `applicants` |
| Database Column | `snake_case` (Singular) | `first_name`, `birth_date`, `is_active` |

### Catatan:
- Nama file Frontend memakai `kebab-case` (contoh: `login-form.tsx`, `dashboard.tsx`) — bukan `PascalCase`.
- Nama komponen React (fungsi yang di-export) tetap `PascalCase` (contoh: `LoginForm`).
- Role pengguna memakai string lowercase: `admin`, `hrd`, `siswa`, `alumni`.

---

## 3. Backend Convention (Laravel 13)

Proyek ini menerapkan pola **Service Pattern** untuk mencegah Fat Controller.

### Controller
- Controller **HANYA** bertugas menerima request, memanggil service, dan mengembalikan response.
- Dilarang menulis logika bisnis atau query database kompleks di dalam Controller.
- Simpan controller API di `app/Http/Controllers/Api/`, controller autentikasi di `app/Http/Controllers/Auth/`.

```php
// ✅ BENAR
public function store(StoreJobVacancyRequest $request): Responsable
{
    $vacancy = $this->jobVacancyService->createJobVacancy(
        $request->validated(),
        $request->user()?->id
    );

    return $this->response
        ->message('Job vacancy created successfully.')
        ->data(new JobVacancyResource($vacancy))
        ->code(201);
}

// ❌ SALAH (Logika campur aduk)
public function store(Request $request)
{
    $request->validate([...]);
    $applicant = new Applicant();
    $applicant->name = $request->name;
    // ...
    $applicant->save();
    return response()->json([...]);
}
```

### Response Service (Wajib)
- Semua response API (success & error) harus melalui `App\Services\ResponseService` (fluent), jangan pakai `response()->json()` manual.
- Gunakan `ResponseService::make()` atau injeksi dependency (`protected ResponseService $response`).

```php
return $this->response
    ->message('Data retrieved successfully.')
    ->data(new XxxResource($data));
```

### Validation
- Wajib menggunakan Form Request untuk validasi. Jangan lakukan validasi di dalam controller (`$request->validate()`).

### API Resources
- Gunakan API Resources (`JsonResource`) untuk mengatur data yang dikirim ke Frontend. Jangan pernah langsung me-return Model Eloquent.
- Petakan `snake_case` database ke `camelCase` untuk Frontend di dalam `toArray()`.

### Otorisasi (RBAC)
- Gunakan middleware `auth:sanctum` untuk endpoint yang butuh login.
- Gunakan middleware `role:[role]` (RBAC) untuk membatasi akses per role (`admin`, `hrd`, `siswa`, `alumni`).

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // API khusus admin
    });
});
```

### Types & Strictness
- Gunakan type hinting pada parameter dan return type pada setiap method.

```php
public function store(StoreApplicantRequest $request): Responsable
{
    //
}
```

### Events & Mail
- **Notifikasi real-time / efek samping:** gunakan Event (`app/Events/`) yang di-broadcast via Reverb.
- **Pengiriman email:** buat Mailable (`app/Mail/`) dan panggil lewat Service (misal `MailService`).

---

## 4. Frontend Convention (React + TypeScript + Vite)

### TypeScript
- Wajib menggunakan TypeScript. Hindari penggunaan `any`. Buat interface atau type untuk semua struktur data.
- Definisi tipe & schema validasi diletakkan di file `schemas/` di dalam fitur terkait (atau `src/types/` bila dipakai global).

### State Management (Redux Toolkit)
- Gunakan Redux Toolkit untuk state global (contoh: autentikasi).
- Satu fitur global = satu file di `src/slices/` (contoh: `authSlice.ts`).
- Gunakan typed hooks dari `src/hooks/useApp.ts` (`useAppDispatch`, `useAppSelector`) — jangan import `useDispatch`/`useSelector` langsung dari `react-redux`.

### Layer Komunikasi Backend
- Selalu pakai instance `api` dari `src/api/axios.ts` (sudah ada interceptor token & handling 401). Jangan buat axios instance baru.
- Token disimpan di `localStorage` dengan key `access_token`; interceptor `api` otomatis menambahkan `Authorization: Bearer ...`.

### Form & Validasi
- Gunakan Zod untuk schema validasi + React Hook Form (`@hookform/resolvers/zod`) untuk handling form.

### Routing (React Router v7)
- Konfigurasi routing utama di `src/route.tsx` menggunakan `createBrowserRouter`.
- Route per fitur didefinisikan di `features/[aktor]/route.tsx` (array), lalu digabung di `src/route.tsx`.
- Proteksi route memakai komponen `ProtectedRoute` dengan prop `allowedRoles`.

### UI Library
- Pakai komponen shadcn/ui (folder `src/components/ui/`). Komponen custom taruh di `src/components/custom/`.

### Data Fetching
- Saat ini tidak memakai React Query. Gunakan pemanggilan `api` (axios) langsung di komponen, atau di-extract ke custom hook bila logika dipakai ulang.
- Selalu handle error (tampilkan message dari response backend).

```tsx
// ✅ BENAR
interface UserProps {
  id: string;
  name: string;
  role: 'admin' | 'student' | 'alumni';
}

const UserCard: React.FC<UserProps> = ({ id, name, role }) => { ... }
```

---

## 5. API Response Convention

Semua response dibentuk oleh `App\Services\ResponseService` dengan format yang konsisten.

### Success Response (200 OK / 201 Created):
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": {
    "id": 1,
    "name": "Tiansyah"
  }
}
```

### Error Response (401 / 403 / 404 / 500):
```json
{
  "success": false,
  "message": "Anda tidak memiliki hak untuk mengakses ini"
}
```

### Catatan:
- Key `message` hanya muncul jika diisi; key `data` hanya muncul jika nilainya tidak null.
- Bila diperlukan, error validasi dapat menambahkan key `errors` melalui payload tambahan `ResponseService` (contoh: `->with('errors', $errors)`).

---

## 6. Database Convention

- **Primary Key:** Gunakan nama `id` dengan tipe bigint auto-increment (`$table->id()`) — proyek ini tidak memakai UUID.
- **Foreign Key:** Gunakan nama tabel singular + `_id` (contoh: `user_id`, `job_vacancy_id`).
- **Timestamps:** Selalu gunakan `created_at` dan `updated_at` (`$table->timestamps()`).
- **Soft Deletes:** Gunakan kolom `deleted_at` (`SoftDeletes`) pada tabel data krusial (contoh: `job_vacancies`, data pelamar).
- **Audit Trail:** Untuk tabel yang butuh rekam jejak, tambahkan kolom `created_by`, `updated_by`, dan `deleted_by` (relasi ke `users`).
- **Lookup / Standard Types:** Untuk nilai enum/status/lookup (jenis lowongan, status lamaran, dll) gunakan pola tabel `standard_types` + `standard_type_categories`, diisi via Seeder, lalu di-relasikan dengan `foreignId`.

---

## 7. Do’s & Don’ts

### ✅ DO (Harus Dilakukan)
- **Don’t Repeat Yourself:** Ekstrak logika yang berulang menjadi helper function, service, atau custom hook.
- **Keep It Simple:** Jangan membuat logika kompleks (overengineering) jika hal sederhana sudah menyelesaikan masalah.
- **Gunakan alat yang sudah ada:** `ResponseService` untuk response Backend, `api` (axios) untuk request Frontend, `ProtectedRoute` untuk proteksi route.

### ❌ DON’T (Dilarang)
- **Hardcode Credentials:** Jangan menyimpan token, secret key, atau password langsung di dalam kode. Selalu gunakan file `.env`.
- **Magic Numbers:** Jangan gunakan angka sembarangan dalam condition (misal: `if (status == 3)`). Gunakan konstanta, Enum, atau Standard Type.
- **`any` di TypeScript:** Jangan gunakan `any` tanpa alasan — definisikan tipe yang jelas.
- **Response manual:** Jangan memakai `response()->json()` langsung di Controller; gunakan `ResponseService`.

---

## 8. Testing & Tooling

- **Backend:** Tulis test dengan Pest (folder `tests/Feature` & `tests/Unit`). Jalankan dengan `composer test` atau `php artisan test`.
- **Format Backend:** Gunakan Laravel Pint (`vendor/bin/pint`).
- **Frontend:** Jalankan lint dengan `npm run lint` (ESLint) dan type-check/build dengan `npm run build` (`tsc -b && vite build`).

---

## 9. AI-Friendly Guidelines

Untuk membantu AI Agent memahami dan melakukan navigasi pada proyek ini dengan baik:

- **Self-Documenting Code:** Pastikan nama variabel dan fungsi bisa langsung menjelaskan fungsinya tanpa perlu komentar panjang.
- **Lihat PROJECT_STRUCTURE.md** untuk peta folder sebelum mengubah file.
- **Perubahan Tampilan / API:**
  - Jika mengubah tampilan → cek `frontend/src/features/[aktor]/pages/` & `components/`.
  - Jika mengubah format/response API → cek `backend/app/Http/Resources/` & `backend/app/Services/ResponseService.php`.
