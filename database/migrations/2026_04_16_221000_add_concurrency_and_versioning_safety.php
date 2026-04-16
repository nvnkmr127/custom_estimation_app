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
        // 1. Add lock_version to estimates for Optimistic Locking
        // This increments on every significant change to ensure two users don't overwrite each other.
        Schema::table('estimates', function (Blueprint $table) {
            $table->integer('lock_version')->default(0)->after('version');
        });

        // 2. Add snapshot_version to estimate_approvals to bind approval to specific content state
        // This ensures an approver is signing off on exactly what they saw.
        Schema::table('estimate_approvals', function (Blueprint $table) {
            $table->integer('snapshot_version')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('lock_version');
        });

        Schema::table('estimate_approvals', function (Blueprint $table) {
            $table->dropColumn('snapshot_version');
        });
    }
};
