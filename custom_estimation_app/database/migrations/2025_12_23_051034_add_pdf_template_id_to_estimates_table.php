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
            if (!Schema::hasColumn('estimates', 'pdf_template_id')) {
                $table->foreignId('pdf_template_id')->nullable()->constrained('pdf_templates')->nullOnDelete()->after('pdf_theme');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['pdf_template_id']);
            $table->dropColumn('pdf_template_id');
        });
    }
};
