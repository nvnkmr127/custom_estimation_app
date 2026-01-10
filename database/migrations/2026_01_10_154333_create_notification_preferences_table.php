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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // e.g., 'estimate.sent', 'estimate.viewed', 'comment.added'
            $table->string('channel')->default('email'); // 'email', 'in_app', etc.
            $table->string('frequency')->default('instant'); // 'instant', 'daily_digest', 'weekly_digest', 'muted'
            $table->timestamps();

            $table->unique(['user_id', 'event_type', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
