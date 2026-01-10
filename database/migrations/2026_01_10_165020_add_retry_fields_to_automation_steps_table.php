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
            $table->integer('retry_limit')->default(0)->after('delay');
            $table->string('on_failure')->default('continue')->after('retry_limit'); // continue, halt
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_steps', function (Blueprint $table) {
            $table->dropColumn(['retry_limit', 'on_failure']);
        });
    }
};
