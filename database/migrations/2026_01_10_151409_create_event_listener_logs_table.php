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
        Schema::create('event_listener_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->index();
            $table->string('listener_class');
            $table->string('status')->default('pending'); // pending, processing, success, failed
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Optional: Foreign key if we want strict integrity, but logging often prefers loose coupling
            // $table->foreign('event_id')->references('event_id')->on('event_logs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_listener_logs');
    }
};
