<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('event_type')->nullable()->after('subject');
            $table->string('entity_type')->nullable()->after('event_type');
            $table->string('entity_id')->nullable()->after('entity_type');
            $table->timestamp('converted_at')->nullable()->after('opened_at');
            $table->integer('conversion_time_seconds')->nullable()->after('converted_at');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn([
                'event_type',
                'entity_type',
                'entity_id',
                'converted_at',
                'conversion_time_seconds',
            ]);
        });
    }
};
