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
        Schema::table('estimates', function (Blueprint $table) {
            $table->decimal('total_cost', 15, 2)->default(0)->after('grand_total')->comment('Total cost of all items');
            $table->decimal('gross_profit', 15, 2)->default(0)->after('total_cost')->comment('Subtotal - Discounts - Cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'gross_profit']);
        });
    }
};
