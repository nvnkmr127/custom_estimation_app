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
        // 1. Automations (Root)
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('condition_logic')->default('AND'); // Global entry logic
            $table->json('settings')->nullable(); // Safety controls, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Automation Triggers (What starts the workflow)
        Schema::create('automation_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automations')->onDelete('cascade');
            $table->string('event_name'); // e.g., 'estimate.created'
            $table->timestamps();
        });

        // 3. Automation Steps (Sequence of actions)
        Schema::create('automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automations')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->integer('delay')->default(0); // in seconds
            $table->string('condition_logic')->default('AND'); // Logic for step execution
            $table->timestamps();
        });

        // 4. Automation Conditions (Global or Step-level)
        Schema::create('automation_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->nullable()->constrained('automations')->onDelete('cascade');
            $table->foreignId('automation_step_id')->nullable()->constrained('automation_steps')->onDelete('cascade');
            $table->string('type'); // payload, entity, counts
            $table->string('field');
            $table->string('operator')->default('=');
            $table->string('value')->nullable();
            $table->timestamps();
        });

        // 5. Automation Actions (Actual work to do)
        Schema::create('automation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_step_id')->constrained('automation_steps')->onDelete('cascade');
            $table->string('type'); // email, webhook, status_update, notification
            $table->json('config'); // template_id, url, message, etc.
            $table->timestamps();
        });

        // 6. Automation Execution Logs (The History)
        Schema::create('automation_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automations')->onDelete('cascade');
            $table->foreignId('automation_step_id')->constrained('automation_steps')->onDelete('cascade');
            $table->string('event_id');
            $table->string('status'); // success, failed, pending
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_execution_logs');
        Schema::dropIfExists('automation_actions');
        Schema::dropIfExists('automation_conditions');
        Schema::dropIfExists('automation_steps');
        Schema::dropIfExists('automation_triggers');
        Schema::dropIfExists('automations');
    }
};
