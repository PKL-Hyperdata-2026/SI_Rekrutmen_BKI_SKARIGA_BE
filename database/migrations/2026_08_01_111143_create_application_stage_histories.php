<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_stage_histories', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (Relasi)
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('selection_stage_id')->constrained('selection_stages')->restrictOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('standard_types')->nullOnDelete(); // Hasil: scheduled, passed, failed, absent
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete(); // Penguji/HRD

            // Contents
            $table->decimal('score', 5, 2)->nullable(); // Nilai/skor tes jika ada (misal: 85.50)
            $table->text('notes')->nullable(); // Catatan atau evaluasi penguji

            // Footer (Audit Trail)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_stage_histories');
    }
};
