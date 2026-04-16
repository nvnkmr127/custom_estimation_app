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
            // Check if the previous optimized index exists and drop it to replace with a better one
            // or just add a new one that includes the ID for the ORDER BY clause.
            // The query used by Laravel is: 
            // select * from jobs where queue = ? and ... order by id asc limit 1 for update
            
            // We'll drop the previous attempt if it exists
            try {
                $table->dropIndex('jobs_queue_reserved_at_available_at_index');
            } catch (\Exception $e) {
                // Ignore if it doesn't exist
            }

            // Create a more robust index that includes id to prevent filesort and reduce locking contention
            $table->index(['queue', 'reserved_at', 'available_at', 'id'], 'jobs_queue_reserved_at_available_at_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_reserved_at_available_at_id_index');
            $table->index(['queue', 'reserved_at', 'available_at'], 'jobs_queue_reserved_at_available_at_index');
        });
    }
};
