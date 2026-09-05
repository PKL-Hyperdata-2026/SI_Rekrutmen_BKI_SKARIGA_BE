<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StudentAlumni;
use App\Models\TracerStudy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TracerStudyService
{
    /**
     * Ambil data tracer study milik alumni yang sedang login.
     */
    public function getAlumniTracerStudy(User $user): ?TracerStudy
    {
        $alumni = StudentAlumni::where('user_id', $user->id)->first();

        if (!$alumni) {
            throw new NotFoundHttpException('Data alumni tidak ditemukan untuk pengguna ini.');
        }

        return TracerStudy::where('student_alumni_id', $alumni->id)->first();
    }

    /**
     * Simpan atau update data tracer study alumni.
     * Membersihkan field yang tidak relevan dengan career_status terpilih.
     *
     * @param array<string, mixed> $data
     */
    public function submitTracerStudy(User $user, array $data): TracerStudy
    {
        $alumni = StudentAlumni::where('user_id', $user->id)->first();

        if (!$alumni) {
            throw new NotFoundHttpException('Data alumni tidak ditemukan untuk pengguna ini.');
        }

        return DB::transaction(function () use ($user, $alumni, $data): TracerStudy {
            $careerStatus = $data['career_status'];

            // Template default: set null semua atribut spesifik status karir
            $payload = [
                'student_alumni_id'   => $alumni->id,
                'career_status'       => $careerStatus,

                // Bekerja
                'company_name'        => null,
                'job_title'           => null,
                'minimum_salary'      => null,
                'maximum_salary'      => null,
                'waiting_period'      => null,
                'start_date'          => null,

                // Wirausaha
                'business_name'       => null,
                'business_address'    => null,
                'instagram_handle'    => null,
                'average_income'      => null,
                'business_field'      => null,
                'business_start_date' => null,

                // Lanjut Studi
                'university_name'     => null,
                'study_program'       => null,

                'updated_by'          => $user->id,
            ];

            // Masukkan data hanya untuk kategori yang dipilih alumni
            if ($careerStatus === 'bekerja') {
                $payload['company_name']   = $data['company_name'] ?? null;
                $payload['job_title']      = $data['job_title'] ?? null;
                $payload['minimum_salary'] = isset($data['minimum_salary']) ? (int) $data['minimum_salary'] : null;
                $payload['maximum_salary'] = isset($data['maximum_salary']) ? (int) $data['maximum_salary'] : null;
                $payload['waiting_period'] = $data['waiting_period'] ?? null;
                $payload['start_date']     = $data['start_date'] ?? null;
            } elseif ($careerStatus === 'wirausaha') {
                $payload['business_name']       = $data['business_name'] ?? null;
                $payload['business_address']    = $data['business_address'] ?? null;
                $payload['instagram_handle']    = $data['instagram_handle'] ?? null;
                $payload['average_income']      = $data['average_income'] ?? null;
                $payload['business_field']      = $data['business_field'] ?? null;
                $payload['business_start_date'] = $data['business_start_date'] ?? null;
            } elseif ($careerStatus === 'lanjut_studi') {
                $payload['university_name'] = $data['university_name'] ?? null;
                $payload['study_program']   = $data['study_program'] ?? null;
            }
            // Untuk 'mencari_pekerjaan', kolom detail sengaja dibiarkan null

            $tracer = TracerStudy::where('student_alumni_id', $alumni->id)->first();

            if ($tracer) {
                $tracer->update($payload);
                return $tracer->fresh();
            }

            $payload['created_by'] = $user->id;
            return TracerStudy::create($payload);
        });
    }
}
