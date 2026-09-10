<?php

declare(strict_types=1);

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
        Schema::create('selection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->enum('admin_selection_status', ['lolos', 'tidak_lolos'])->default('lolos');
            $table->decimal('psychotest_score', 5, 2)->nullable();
            $table->decimal('interview_score', 5, 2)->nullable();
            $table->decimal('mcu_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->enum('decision', ['diterima', 'tidak_diterima', 'cadangan', 'pending'])->default('pending');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selection_results');
    }
};
