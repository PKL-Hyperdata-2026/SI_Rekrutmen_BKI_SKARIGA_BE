<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracer_studies', function (Blueprint $table) {
            $table->id();

            // Foreign Key ke Alumni
            $table->foreignId('student_alumni_id')
                ->constrained('students_alumni')
                ->cascadeOnDelete();

            // Status Karir (Enum snake_case sesuai instruksi Marvell)
            $table->enum('career_status', [
                'bekerja',
                'wirausaha',
                'lanjut_studi',
                'mencari_pekerjaan',
            ]);

            // Field Khusus Status: Bekerja
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->unsignedBigInteger('minimum_salary')->nullable();
            $table->unsignedBigInteger('maximum_salary')->nullable();
            $table->string('waiting_period')->nullable();
            $table->date('start_date')->nullable();

            // Field Khusus Status: Wirausaha
            $table->string('business_name')->nullable();
            $table->text('business_address')->nullable();
            $table->string('instagram_handle')->nullable();
            $table->string('average_income')->nullable();
            $table->string('business_field')->nullable();
            $table->date('business_start_date')->nullable();

            // Field Khusus Status: Lanjut Studi
            $table->string('university_name')->nullable();
            $table->string('study_program')->nullable();

            // Audit Trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            // Timestamps & Soft Delete
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_studies');
    }
};