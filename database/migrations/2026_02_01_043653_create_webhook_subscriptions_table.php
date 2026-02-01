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
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->text('target_url');
            $table->string('secret_key')->nullable();
            $table->json('subscribed_events')->nullable(); // e.g., ["estimate.*"]
            $table->json('headers')->nullable();           // Custom headers
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('active'); // active, degraded, paused
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
