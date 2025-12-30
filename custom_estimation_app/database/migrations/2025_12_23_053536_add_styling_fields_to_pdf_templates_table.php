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
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->string('primary_color')->default('#333333')->after('orientation');
            $table->string('secondary_color')->default('#555555')->after('primary_color');
            $table->string('font_family')->default('Helvetica')->after('secondary_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'secondary_color', 'font_family']);
        });
    }
};
