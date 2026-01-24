<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add cost column to estimate_items
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->decimal('cost', 15, 2)->nullable()->after('unit_price')->comment('Unit cost for margin calculation');
        });

        // 2. Add Tax Calculation Method Setting
        // Check if settings table exists (it should), insert default
        if (Schema::hasTable('settings')) {
            DB::table('settings')->insertOrIgnore([
                'key' => 'tax_calculation_method',
                'value' => 'subtotal_minus_discount', // Default: Current behavior
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropColumn('cost');
        });

        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'tax_calculation_method')->delete();
        }
    }
};
