<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->boolean('is_incremental')->default(false)->after('backup_type');
            $table->foreignId('parent_id')->nullable()->constrained('backup_logs')->onDelete('set null')->after('is_incremental');
            $table->json('manifest')->nullable()->after('google_drive_url'); // Stores file list and hashes
            $table->string('hash')->nullable()->after('manifest'); // Integrity hash of the ZIP
        });
    }

    public function down(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['is_incremental', 'parent_id', 'manifest', 'hash']);
        });
    }
};
