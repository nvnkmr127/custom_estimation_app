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
        // 1. Create nurture_rules table
        Schema::create('nurture_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_type'); // 'time_based', 'engagement_based', 'expiry_based'
            $table->integer('trigger_days')->default(0); // e.g., 3 days
            $table->json('condition_logic')->nullable(); // e.g., {"view_count": 0}
            $table->string('action_type'); // 'email', 'alert_staff', 'auto_discount'
            $table->json('action_data')->nullable(); // email content or discount details
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add columns to estimates table
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('nurture_status')->default('active')->after('status'); // 'active', 'completed', 'stopped'
            $table->integer('engagement_score')->default(0)->after('nurture_status');
            $table->timestamp('last_engagement_at')->nullable()->after('engagement_score');
            $table->timestamp('last_nurtured_at')->nullable()->after('last_engagement_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['nurture_status', 'engagement_score', 'last_engagement_at', 'last_nurtured_at']);
        });

        Schema::dropIfExists('nurture_rules');
    }
};
