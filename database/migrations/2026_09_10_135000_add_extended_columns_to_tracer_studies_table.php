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
        Schema::table('tracer_studies', function (Blueprint $table) {
            $table->date('accepted_date')->nullable()->after('waiting_period');
            $table->string('job_location')->nullable()->after('job_title');
            $table->string('company_sector')->nullable()->after('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracer_studies', function (Blueprint $table) {
            $table->dropColumn(['accepted_date', 'job_location', 'company_sector']);
        });
    }
};
