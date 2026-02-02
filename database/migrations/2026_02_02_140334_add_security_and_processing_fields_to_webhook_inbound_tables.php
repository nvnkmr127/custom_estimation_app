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
        Schema::table('webhook_inbound_endpoints', function (Blueprint $table) {
            $table->string('secret')->nullable()->after('description');
            $table->string('signature_header')->nullable()->after('secret')->comment('Header key to check for signature');
            $table->text('ip_whitelist')->nullable()->after('signature_header')->comment('Comma separated IPs');
        });

        Schema::table('webhook_inbound_events', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('status');
            // processed_at already exists in the original migration
            $table->integer('attempt_count')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_inbound_endpoints', function (Blueprint $table) {
            $table->dropColumn(['secret', 'signature_header', 'ip_whitelist']);
        });

        Schema::table('webhook_inbound_events', function (Blueprint $table) {
            $table->dropColumn(['error_message', 'attempt_count']);
        });
    }
};
