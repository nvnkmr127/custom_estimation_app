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
            $table->enum('section_type', ['room', 'package'])->default('room')->after('name');
        });

        // Migrate data
        \Illuminate\Support\Facades\DB::table('estimate_sections')->where('is_package', 1)->update(['section_type' => 'package']);

        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->dropColumn('is_package');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->boolean('is_package')->default(false)->after('name');
        });

        // Restore data
        \Illuminate\Support\Facades\DB::table('estimate_sections')->where('section_type', 'package')->update(['is_package' => 1]);

        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->dropColumn('section_type');
        });
    }
};
