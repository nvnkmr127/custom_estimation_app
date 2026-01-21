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
        Schema::create('automation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable(); // Friendly name for the schedule
            $table->string('cron_expression'); // e.g., "0 9 * * 1-5" (9 AM weekdays)
            $table->string('timezone')->default('UTC'); // User's timezone
            $table->timestamp('next_run_at')->nullable(); // Calculated next execution time
            $table->timestamp('last_run_at')->nullable(); // Last execution time
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional configuration
            $table->timestamps();

            // Indexes for performance
            $table->index(['automation_id', 'is_active']);
            $table->index('next_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_schedules');
    }
};
