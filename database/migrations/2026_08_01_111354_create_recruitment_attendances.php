<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_attendances', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (Relasi)
            $table->foreignId('stage_history_id')->constrained('application_stage_histories')->cascadeOnDelete();
            $table->foreignId('attendance_status_id')->nullable()->constrained('standard_types')->nullOnDelete(); // hadir, izin, alpa

            // Contents
            $table->string('qr_code_token')->nullable(); // Token unik verifikasi QR Code presensi
            $table->timestamp('attended_at')->nullable(); // Waktu presensi dicatat
            $table->decimal('latitude', 10, 8)->nullable(); // Koordinat GPS presensi
            $table->decimal('longitude', 11, 8)->nullable(); // Koordinat GPS presensi
            $table->string('photo_selfie_path')->nullable(); // Path foto bukti kehadiran di storage

            // Footer (Audit Trail)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_attendances');
    }
};
