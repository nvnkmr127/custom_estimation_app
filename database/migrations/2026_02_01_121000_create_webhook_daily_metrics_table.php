<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->uuid('webhook_id')->index()->nullable(); // Nullable for global stats if needed, or mandatory per-hook

            $table->unsignedBigInteger('delivery_success_total')->default(0);
            $table->unsignedBigInteger('delivery_failure_total')->default(0);
            $table->unsignedBigInteger('retry_count')->default(0);
            $table->unsignedBigInteger('total_latency_ms')->default(0);
            $table->unsignedBigInteger('dlq_additions')->default(0); // Proxies for DLQ "size" growth

            $table->unique(['date', 'webhook_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_daily_metrics');
    }
};
