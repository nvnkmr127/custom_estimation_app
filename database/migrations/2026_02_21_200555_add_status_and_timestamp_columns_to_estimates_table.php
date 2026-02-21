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
        Schema::table('estimates', function (Blueprint $table) {
            // estimate_status: Internal lifecycle (draft, active, expired, void)
            $table->string('estimate_status')->default('draft')->after('status');

            // client_status: External customer interaction (draft, sent, viewed, accepted, declined)
            $table->string('client_status')->default('draft')->after('approval_status');

            // Timestamps for lifecycle events
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn([
                'estimate_status',
                'client_status',
                'sent_at',
                'expires_at',
                'accepted_at',
                'declined_at'
            ]);
        });
    }
};
