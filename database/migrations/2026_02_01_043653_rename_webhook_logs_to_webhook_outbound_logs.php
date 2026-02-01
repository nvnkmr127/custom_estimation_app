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
        Schema::rename('webhook_logs', 'webhook_outbound_logs');

        Schema::table('webhook_outbound_logs', function (Blueprint $table) {
            $table->uuid('subscription_id')->nullable()->after('id')->index();
            $table->json('request_headers')->nullable()->after('payload');
            $table->integer('latency_ms')->change(); // Ensure it's integer if designs say so, or keep if float ok.
            $table->timestamp('next_retry_at')->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_outbound_logs', function (Blueprint $table) {
            $table->dropColumn(['subscription_id', 'request_headers', 'next_retry_at']);
        });

        Schema::rename('webhook_outbound_logs', 'webhook_logs');
    }
};
