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
            $table->string('type')->default('custom')->after('name'); // system vs custom
            $table->text('description')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['type', 'description']);
        });
    }
};
