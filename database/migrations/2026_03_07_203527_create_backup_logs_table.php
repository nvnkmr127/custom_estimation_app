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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');                      // relative path on local disk
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('disk')->default('local');    // which disk
            $table->string('status')->default('pending'); // pending|completed|failed|uploading_drive|drive_uploaded
            $table->string('triggered_by')->default('system'); // system|manual|schedule
            $table->string('google_drive_id')->nullable();
            $table->string('google_drive_url')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
