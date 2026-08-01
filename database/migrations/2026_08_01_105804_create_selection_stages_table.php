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
        Schema::create('selection_stages', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (Relasi)
            $table->foreignId('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->foreignId('stage_type_id')->nullable()->constrained('standard_types')->nullOnDelete(); // Administrasi, Psikotes, MCU, dll

            // Contents
            $table->string('name'); // Nama tahapan (misal: "Tes Logika & Koding")
            $table->integer('sequence_order')->default(1); // Urutan tahapan (1, 2, 3...)
            $table->text('description')->nullable(); // Deskripsi atau instruksi tahapan
            $table->dateTime('scheduled_at')->nullable(); // Jadwal pelaksanaan tahapan
            $table->string('location')->nullable(); // Lokasi tes/wawancara (fisik/online link)

            // Footer (Audit Trail & Soft Deletes)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selection_stages');
    }
};
