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
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->string('paper_size')->default('a4')->after('css_content'); // a4, letter
            $table->string('orientation')->default('portrait')->after('paper_size'); // portrait, landscape
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['paper_size', 'orientation']);
        });
    }
};
