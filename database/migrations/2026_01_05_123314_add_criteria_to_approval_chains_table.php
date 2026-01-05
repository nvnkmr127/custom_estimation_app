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
        Schema::table('approval_chains', function (Blueprint $table) {
            $table->decimal('min_amount', 15, 2)->nullable()->after('description');
            $table->decimal('max_amount', 15, 2)->nullable()->after('min_amount');
            $table->string('currency', 3)->default('INR')->after('max_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_chains', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount', 'currency']);
        });
    }
};
