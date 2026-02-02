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
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->unsignedBigInteger('original_item_id')->nullable()->after('id')->index();
            // Self-referencing FK, but since versions might be deleted, we might not want strict cascade or constraints that block deletion of old ones easily.
            // For now, simple index is enough for tracking logic, but FK provides integrity.
            // Let's rely on loose coupling for now to allow deletion of old versions without blocking.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropColumn('original_item_id');
        });
    }
};
