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
            $table->decimal('min_discount_percentage', 5, 2)->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_chains', function (Blueprint $table) {
            $table->dropColumn('min_discount_percentage');
        });
    }
};
