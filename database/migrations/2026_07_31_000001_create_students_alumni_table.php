<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students_alumni', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (Relasi)
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete(); // nullable = admin bisa input manual; unique = 1 user = 1 record
            $table->foreignId('major_id')->constrained('majors')->restrictOnDelete(); // aman dari human error
            $table->foreignId('employment_status_id')->nullable()->constrained('standard_types')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('standard_types')->nullOnDelete(); // kategori 'class'
            $table->foreignId('current_company_id')->nullable()->constrained('companies')->nullOnDelete();

            // Contents
            $table->string('nis', 20)->unique()->nullable();
            $table->year('graduation_year')->nullable(); // null = siswa aktif, terisi = alumni
            $table->json('social_media')->nullable();
            $table->string('current_position')->nullable();
            $table->decimal('starting_salary', 12, 2)->nullable(); // IDR/bulan
            $table->integer('waiting_time_months')->nullable();   // masa tunggu kerja (bulan)
            $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('students_alumni');
    }
};
