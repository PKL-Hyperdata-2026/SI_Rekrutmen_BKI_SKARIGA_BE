<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recruitment_attendances', function (Blueprint $table) {
            $table->string('validation_status', 20)->default('pending')->after('attendance_status_id');
            $table->foreignId('validated_by')->nullable()->after('validation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->text('notes')->nullable()->after('validated_at');
            $table->string('system_action')->nullable()->after('notes');

            $table->index(['validation_status', 'attended_at']);
            $table->index(['validation_status', 'validated_at']);
            $table->index(['validation_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_attendances', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropIndex(['validation_status', 'attended_at']);
            $table->dropIndex(['validation_status', 'validated_at']);
            $table->dropIndex(['validation_status', 'created_at']);
            $table->dropColumn([
                'validation_status',
                'validated_by',
                'validated_at',
                'notes',
                'system_action',
            ]);
        });
    }
};
