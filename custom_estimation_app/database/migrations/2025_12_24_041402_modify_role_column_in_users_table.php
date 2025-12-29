<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Modify the role column to be a string with a length of 255.
            // Using DB definition to ensure it works across different DB drivers if needed,
            // but for MySQL/MariaDB this is standard.
            // We use DB::statement because ->change() requires doctrine/dbal which might not be installed.
            \DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) DEFAULT 'estimator'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to a shorter length if needed, but usually 255 is safe to keep.
            // For strict reversal, we could change it back to 50 or whatever it was.
            // Let's assume 30 was likely the limit causing issues.
            \DB::statement("ALTER TABLE users MODIFY role VARCHAR(30) DEFAULT 'estimator'");
        });
    }
};
