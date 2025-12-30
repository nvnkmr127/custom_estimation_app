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
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('approval_chains', function (Blueprint $table) {
            $table->foreignId('fallback_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('approval_chain_steps', function (Blueprint $table) {
            $table->integer('timeout_hours')->nullable();
            $table->string('timeout_action')->default('notify'); // notify, skip, reject
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_chain_steps', function (Blueprint $table) {
            $table->dropColumn(['timeout_hours', 'timeout_action']);
        });

        Schema::table('approval_chains', function (Blueprint $table) {
            $table->dropForeign(['fallback_user_id']);
            $table->dropColumn('fallback_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
