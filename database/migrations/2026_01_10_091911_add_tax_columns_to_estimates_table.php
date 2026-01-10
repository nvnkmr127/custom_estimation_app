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
        Schema::table('estimates', function (Blueprint $blueprint) {
            $blueprint->decimal('tax_1', 5, 2)->default(0)->after('subtotal');
            $blueprint->decimal('tax_2', 5, 2)->default(0)->after('tax_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['tax_1', 'tax_2']);
        });
    }
};
