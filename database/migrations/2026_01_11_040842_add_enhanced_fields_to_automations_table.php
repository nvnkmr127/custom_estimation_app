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
        Schema::table('automations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->foreignId('last_edited_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            $table->timestamp('last_edited_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropForeign(['last_edited_by']);
            $table->dropColumn(['description', 'last_edited_by', 'last_edited_at']);
        });
    }
};
