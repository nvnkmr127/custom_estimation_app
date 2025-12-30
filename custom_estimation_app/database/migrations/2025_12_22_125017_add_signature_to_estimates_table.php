<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->longText('signature')->nullable()->after('status');
            $table->timestamp('signed_at')->nullable()->after('signature');
            $table->string('signer_ip')->nullable()->after('signed_at');
            $table->text('client_notes')->nullable()->after('signer_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['signature', 'signed_at', 'signer_ip', 'client_notes']);
        });
    }
};
