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
        Schema::create('automation_experiments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_event')->index(); // The event this experiment controls
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, running, paused, completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('winning_variant_id')->nullable(); // Set when manual or auto winner declared
            $table->timestamps();
        });

        Schema::create('automation_experiment_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('automation_experiments')->cascadeOnDelete();
            $table->foreignId('automation_id')->constrained('automations'); // The actual automation rule to run
            $table->integer('weight')->default(50); // Traffic percentage (0-100)
            $table->timestamps();

            // Performance snapshots (updated by analytics job)
            $table->integer('executions_count')->default(0);
            $table->integer('conversions_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_experiment_variants');
        Schema::dropIfExists('automation_experiments');
    }
};
