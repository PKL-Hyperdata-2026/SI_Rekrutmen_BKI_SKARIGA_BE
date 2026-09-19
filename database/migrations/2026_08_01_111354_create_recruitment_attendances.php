<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stage_history_id')->constrained('application_stage_histories')->cascadeOnDelete();
            $table->foreignId('attendance_status_id')->nullable()->constrained('standard_types')->nullOnDelete();
            $table->timestamp('attended_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_attendances');
    }
};
