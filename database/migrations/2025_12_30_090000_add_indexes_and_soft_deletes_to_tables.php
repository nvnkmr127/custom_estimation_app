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
        // 1. Add Soft Deletes to existing tables
        Schema::table('estimates', function (Blueprint $table) {
            if (! Schema::hasColumn('estimates', 'deleted_at')) {
                $table->softDeletes();
            }

            if (! Schema::hasColumn('estimates', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('estimate_comments', function (Blueprint $table) {
            if (! Schema::hasColumn('estimate_comments', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 2. Add indexes to other tables for performance where missing
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->index('order_index');
        });

        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->index('order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->dropIndex(['order_index']);
        });

        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropIndex(['order_index']);
        });

        Schema::table('estimate_comments', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'deleted_at']);
        });
    }
};
