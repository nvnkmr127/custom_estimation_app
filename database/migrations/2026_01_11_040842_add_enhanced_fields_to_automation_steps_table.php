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
        Schema::table('automation_steps', function (Blueprint $table) {
            $table->text('description')->nullable()->after('condition_logic');
            $table->boolean('is_enabled')->default(true)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_steps', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_enabled']);
        });
    }
};
