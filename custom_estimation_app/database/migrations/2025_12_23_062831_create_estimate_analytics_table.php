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
        Schema::create('estimate_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'view', 'download'
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device')->nullable(); // Mobile, Desktop, Tablet, Robot
            $table->string('browser')->nullable();
            $table->string('platform')->nullable(); // OS
            $table->text('location_json')->nullable(); // JSON data for city, country
            $table->boolean('is_unique')->default(false);
            $table->timestamps();

            $table->index(['estimate_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_analytics');
    }
};
