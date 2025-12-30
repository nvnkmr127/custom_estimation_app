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
            $table->foreignId('coupon_code_id')->nullable()->after('discount_total')->constrained()->nullOnDelete();
            $table->decimal('coupon_discount', 10, 2)->default(0)->after('coupon_code_id');
            $table->decimal('item_discounts_total', 10, 2)->default(0)->after('coupon_discount');
            $table->decimal('room_discounts_total', 10, 2)->default(0)->after('item_discounts_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['coupon_code_id']);
            $table->dropColumn(['coupon_code_id', 'coupon_discount', 'item_discounts_total', 'room_discounts_total']);
        });
    }
};
