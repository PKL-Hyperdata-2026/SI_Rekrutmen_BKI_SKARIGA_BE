<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            //Foreign Keys (Relasi)
            $table->foreignId('job_vacancy_id')->constrained('job_vacancies')->restrictOnDelete();
            $table->foreignId('student_alumni_id')->constrained('students_alumni')->restrictOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('standard_types')->nullOnDelete(); // Status utama: pending, in_progres, accepted, rejected
            $table->foreignId('current_stage_id')->nullable()->constrained('selection_stages')->nullOnDelete(); // Tahapan aktif pelamar saat ini

            //  Contents
            $table->dateTime('applied_at'); // Waktu submit lamaran
            $table->text('notes')->nullable(); // Catatan khusus dari Admin/HRD

            // Footer (Audit Trail & Soft Deletes)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
