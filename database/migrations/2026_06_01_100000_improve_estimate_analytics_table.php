<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_analytics', function (Blueprint $table) {
            // Change location_json from text to proper JSON column
            $table->json('location_json')->nullable()->change();

            // Add missing indexes for common query patterns
            $table->index(['estimate_id', 'created_at']);
            $table->index(['estimate_id', 'is_unique']);
            $table->index(['device']);
            $table->index(['browser']);
        });
    }

    public function down(): void
    {
        Schema::table('estimate_analytics', function (Blueprint $table) {
            $table->text('location_json')->nullable()->change();
            $table->dropIndex(['estimate_id', 'created_at']);
            $table->dropIndex(['estimate_id', 'is_unique']);
            $table->dropIndex(['device']);
            $table->dropIndex(['browser']);
        });
    }
};
