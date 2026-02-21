<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('estimates')
                ->whereNotIn('approval_status', ['draft', 'submitted', 'approved', 'rejected'])
                ->update(['approval_status' => 'draft']);

            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM('draft', 'submitted', 'pending', 'approved', 'rejected', 'changes_requested') DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft'");
        }
    }
};
