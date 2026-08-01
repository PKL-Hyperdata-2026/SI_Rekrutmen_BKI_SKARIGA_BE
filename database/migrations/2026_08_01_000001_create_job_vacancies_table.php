<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();

            // Relasi Perusahaan
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();

            // Lookup Standard Types
            $table->foreignId('job_type_id')->nullable()->constrained('standard_types')->nullOnDelete(); 
            $table->foreignId('status_id')->nullable()->constrained('standard_types')->nullOnDelete();
            $table->foreignId('target_applicant_id')->nullable()->constrained('standard_types')->nullOnDelete();

            // Detail Lowongan & SEO
            $table->string('title');
            $table->string('slug')->unique()->nullable();
            $table->string('position')->nullable();
            $table->text('description')->nullable();
            $table->text('qualification')->nullable();
            $table->integer('quota')->default(1);
            $table->date('deadline')->nullable();
            $table->string('work_location')->nullable();
            
            // Gaji (Range)
            $table->decimal('min_salary', 15, 2)->nullable();
            $table->decimal('max_salary', 15, 2)->nullable();

            // Highlighting & Status Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);

            // Audit Trail & Soft Deletes
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for Performance Optimization
            $table->index(['status_id', 'deadline']);
            $table->index(['company_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_vacancies');
    }
};
