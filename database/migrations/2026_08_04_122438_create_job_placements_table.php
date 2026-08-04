<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_placements', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (Relasi)
            $table->foreignId('job_application_id')->nullable()->constrained('job_applications')->nullOnDelete();
            $table->foreignId('student_alumni_id')->constrained('students_alumni')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('placement_status_id')->nullable()->constrained('standard_types')->nullOnDelete();

            // Contents
            $table->date('accepted_date')->nullable(); //tgl diterima
            $table->date('start_date')->nullable(); //tgl mulai kerja
            $table->text('notes')->nullable();

            // Footer (Audit Trail & Soft Deletes)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['placement_status_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_placements');
    }
};