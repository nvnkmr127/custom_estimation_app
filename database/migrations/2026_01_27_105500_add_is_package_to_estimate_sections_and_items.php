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
        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->boolean('is_package')->default(false)->after('name');
        });

        Schema::table('estimate_items', function (Blueprint $table) {
            $table->boolean('is_package')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->dropColumn('is_package');
        });

        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropColumn('is_package');
        });
    }
};
