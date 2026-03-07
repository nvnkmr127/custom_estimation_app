<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            // 'checkpoint' = fast DB-only intra-day backup
            // 'final'      = full backup (DB + storage), kept long-term
            $table->string('backup_type')->default('final')->after('triggered_by');
        });
    }

    public function down(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->dropColumn('backup_type');
        });
    }
};
