<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selection_results', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('status');
        });

        DB::table('selection_results')
            ->where('status', 'published')
            ->whereNull('published_at')
            ->update(['published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('selection_results', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
