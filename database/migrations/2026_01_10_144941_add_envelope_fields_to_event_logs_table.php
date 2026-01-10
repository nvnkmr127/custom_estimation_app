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
        Schema::table('event_logs', function (Blueprint $table) {
            $table->string('entity_type')->nullable()->index()->after('event_name');
            $table->string('entity_id')->nullable()->index()->after('entity_type'); // String to support UUIDs or diverse IDs
            $table->unsignedBigInteger('triggered_by')->nullable()->index()->after('payload');
            $table->string('source')->default('web')->index()->after('triggered_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'entity_id', 'triggered_by', 'source']);
        });
    }
};
