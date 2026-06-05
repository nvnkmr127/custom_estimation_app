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
        if (!Schema::hasColumn('email_templates', 'event_trigger')) {
            Schema::table('email_templates', function (Blueprint $blueprint) {
                $blueprint->string('event_trigger')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $blueprint) {
            $blueprint->dropColumn('event_trigger');
        });
    }
};
