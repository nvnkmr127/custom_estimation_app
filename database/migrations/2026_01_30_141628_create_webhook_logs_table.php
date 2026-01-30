<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('webhook_url');
            $table->string('event_id')->nullable();
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->float('latency_ms')->nullable();
            $table->integer('attempt')->default(1);
            $table->string('status'); // success, failed, retrying
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
