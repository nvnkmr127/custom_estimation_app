<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Endpoint Configuration
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Human readable name (e.g., "Zapier Integration")');
            $table->string('url')->comment('Target Endpoint URL');
            $table->string('secret')->nullable()->comment('Signing Secret (HMAC)');
            $table->json('headers')->nullable()->comment('Custom static headers');
            $table->json('events')->comment('Array of subscribed event types (e.g., ["estimate.*"])');
            $table->enum('status', ['active', 'inactive', 'disabled_by_system'])->default('active');
            $table->unsignedInteger('concurrency_limit')->default(5);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        // 2. Event Store (Source of Truth)
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type')->index(); // e.g., estimate.created
            $table->string('idempotency_key')->unique()->comment('Unique key to prevent duplicate processing');
            $table->json('payload')->comment('Normalized payload data');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        // 3. Delivery Attempts (The "Log")
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('webhook_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('webhook_event_id')->constrained('webhook_events')->cascadeOnDelete();

            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'retrying'])->default('pending')->index();
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->timestamp('next_retry_at')->nullable()->index();

            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable(); // Truncated?
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Composite index for "Get me pending jobs for this webhook"
            $table->index(['webhook_id', 'status', 'next_retry_at']);
        });

        // 4. Dead Letters (Final Resting Place)
        Schema::create('webhook_dead_letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('original_delivery_id')->constrained('webhook_deliveries');
            $table->json('snapshot_payload')->comment('Payload at time of failure');
            $table->text('final_error_message');
            $table->timestamp('failed_at')->useCurrent();
            $table->boolean('replayed')->default(false);
        });

        // 5. Mappings (Optional Transformations)
        Schema::create('webhook_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('internal_event_type');
            $table->string('external_event_type')->nullable(); // If simple rename
            $table->json('transformation_template')->nullable()->comment('JSONPath or Template for reshaping');
            $table->timestamps();

            $table->unique(['webhook_id', 'internal_event_type']);
        });

        // 6. Daily Metrics (Aggregation)
        Schema::create('webhook_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignUuid('webhook_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('total_events')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->unsignedBigInteger('total_duration_ms')->default(0);

            $table->timestamps();

            $table->unique(['date', 'webhook_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_metrics_daily');
        Schema::dropIfExists('webhook_mappings');
        Schema::dropIfExists('webhook_dead_letters');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('webhooks');
    }
};
