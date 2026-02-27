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
        Schema::table('jobs', function (Blueprint $table) {
            // Drop existing single-column index if possible, 
            // but to be safe across environments, we'll just add the more specific one.
            // Laravel's database queue driver benefits greatly from this composite index.
            $table->index(['queue', 'reserved_at', 'available_at'], 'jobs_queue_reserved_at_available_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_reserved_at_available_at_index');
        });
    }
};
