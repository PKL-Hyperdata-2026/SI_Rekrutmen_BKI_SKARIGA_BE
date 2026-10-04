<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code' => 'job_application',
                'name' => 'Notifikasi Lamaran Baru (Untuk HRD)',
                'channel' => 'both',
                'subject' => 'Lamaran Baru: {posisi}',
                'body' => '{nama} telah mengirimkan lamaran pekerjaan untuk posisi {posisi}.',
                'variables' => ['nama', 'posisi', 'perusahaan'],
            ],
            [
                'code' => 'admin_review_failed',
                'name' => 'Hasil Seleksi Administrasi Tidak Lolos',
                'channel' => 'notification',
                'subject' => 'Hasil Seleksi Administrasi: {posisi}',
                'body' => 'Terima kasih atas partisipasi Anda pada seleksi {posisi} di {perusahaan}. Saat ini berkas Anda belum lolos ke tahap berikutnya. Tetap semangat!',
                'variables' => ['posisi', 'perusahaan', 'catatan'],
            ],
            [
                'code' => 'test_scheduled',
                'name' => 'Undangan Jadwal Tes Seleksi',
                'channel' => 'both',
                'subject' => 'Jadwal Tes Seleksi: {tahap}',
                'body' => 'Anda dijadwalkan mengikuti {tahap} pada {tanggal} pukul {jam} di {lokasi}.',
                'variables' => ['tahap', 'tanggal', 'jam', 'lokasi', 'instruksi'],
            ],
            [
                'code' => 'test_reminder',
                'name' => 'Pengingat Agenda Tes Seleksi',
                'channel' => 'both',
                'subject' => 'Pengingat: Jadwal {tahap} Besok',
                'body' => 'Mengingatkan kembali bahwa Anda memiliki agenda {tahap} pada {tanggal} pukul {jam} di {lokasi}.',
                'variables' => ['tahap', 'tanggal', 'jam', 'lokasi'],
            ],
            [
                'code' => 'attendance_validated',
                'name' => 'Presensi Kehadiran Terverifikasi',
                'channel' => 'notification',
                'subject' => 'Presensi Terverifikasi: {tahap}',
                'body' => 'Presensi kehadiran Anda untuk {tahap} pada lowongan {posisi} telah diverifikasi oleh panitia.',
                'variables' => ['tahap', 'posisi'],
            ],
            [
                'code' => 'attendance_rejected',
                'name' => 'Presensi Kehadiran Ditolak / Tidak Hadir',
                'channel' => 'notification',
                'subject' => 'Presensi Ditolak: {tahap}',
                'body' => 'Presensi kehadiran Anda untuk {tahap} pada lowongan {posisi} ditolak. {catatan}',
                'variables' => ['tahap', 'posisi', 'catatan'],
            ],
            [
                'code' => 'selection_accepted',
                'name' => 'Pengumuman Diterima Bekerja',
                'channel' => 'both',
                'subject' => 'Pengumuman Kelulusan: {posisi}',
                'body' => 'Selamat! Anda dinyatakan DITERIMA pada posisi {posisi} ({judul}). Silakan unduh surat penempatan di portal siswa.',
                'variables' => ['posisi', 'judul', 'perusahaan'],
            ],
            [
                'code' => 'selection_failed',
                'name' => 'Pengumuman Belum Lolos Seleksi Akhir',
                'channel' => 'notification',
                'subject' => 'Pengumuman Kelulusan: {posisi}',
                'body' => 'Terima kasih telah berpartisipasi pada seleksi {judul}. Saat ini Anda belum berhasil lolos. Tetap semangat!',
                'variables' => ['posisi', 'judul', 'perusahaan'],
            ],
            [
                'code' => 'tracer_study_reminder',
                'name' => 'Pengingat Pembaruan Tracer Study',
                'channel' => 'both',
                'subject' => 'Pengingat Pembaruan Status Karier & Tracer Study',
                'body' => 'Halo! Mohon luangkan waktu untuk memperbarui data penempatan kerja dan Tracer Study Anda.',
                'variables' => ['perusahaan'],
            ],
        ];

        foreach ($templates as $t) {
            MessageTemplate::updateOrCreate(
                ['code' => $t['code']],
                [
                    'name' => $t['name'],
                    'channel' => $t['channel'],
                    'subject' => $t['subject'],
                    'body' => $t['body'],
                    'variables' => $t['variables'],
                    'is_active' => true,
                ]
            );
        }
    }
}
