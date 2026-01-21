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
        Schema::create('automation_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automations')->cascadeOnDelete();
            $table->date('date')->index();
            $table->integer('total_executions')->default(0);
            $table->integer('successful_executions')->default(0);
            $table->integer('failed_executions')->default(0);
            $table->integer('total_duration_ms')->default(0); // For avg calculation
            $table->timestamps();

            $table->unique(['automation_id', 'date']);
        });

        Schema::create('automation_step_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_step_id')->constrained('automation_steps')->cascadeOnDelete();
            $table->date('date')->index();
            $table->integer('run_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamps();

            $table->unique(['automation_step_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_step_stats');
        Schema::dropIfExists('automation_daily_stats');
    }
};
