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
            // Drop the existing foreign key with cascade
            $table->dropForeign(['parent_id']);

            // Re-add the foreign key with SET NULL instead of CASCADE
            // This prevents cascade deletion and preserves version history
            $table->foreign('parent_id')
                ->references('id')
                ->on('estimates')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            // Drop the SET NULL foreign key
            $table->dropForeign(['parent_id']);

            // Restore the original CASCADE foreign key
            $table->foreign('parent_id')
                ->references('id')
                ->on('estimates')
                ->onDelete('cascade');
        });
    }
};
