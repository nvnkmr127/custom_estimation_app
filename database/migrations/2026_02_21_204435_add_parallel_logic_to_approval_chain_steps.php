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
        Schema::table('approval_chain_steps', function (Blueprint $table) {
            $table->boolean('require_all')->default(true)->after('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_chain_steps', function (Blueprint $table) {
            $table->dropColumn('require_all');
        });
    }
};
