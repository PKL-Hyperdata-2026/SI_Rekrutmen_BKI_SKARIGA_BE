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
        Schema::table('job_placements', function (Blueprint $table) {
            $table->json('evaluations')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('job_placements', function (Blueprint $table) {
            $table->dropColumn('evaluations');
        });
    }
};
