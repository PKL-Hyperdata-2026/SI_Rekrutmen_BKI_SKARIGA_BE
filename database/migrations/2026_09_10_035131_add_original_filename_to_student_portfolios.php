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
        Schema::table('student_portfolios', function (Blueprint $table): void {
            $table->string('original_filename', 255)->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('student_portfolios', function (Blueprint $table): void {
            $table->dropColumn('original_filename');
        });
    }
};
