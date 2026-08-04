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
            
            // Foreign Keys
            $table->foreignId('student_alumni_id')
                  ->constrained('students_alumni')
                  ->cascadeOnDelete();
                  
            $table->foreignId('employment_status_id')
                  ->nullable()
                  ->constrained('standard_types');
                  
            $table->year('survey_year');
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            
            $table->foreignId('relevance_status_id')
                  ->nullable()
                  ->constrained('standard_types');
                  
            $table->foreignId('income_range_id')
                  ->nullable()
                  ->constrained('standard_types');

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