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
        Schema::table('products', function (Blueprint $table) {
            // Only add fields that don't exist
            if (!Schema::hasColumn('products', 'status')) {
                $table->string('status')->default('active')->after('image_path');
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (!Schema::hasColumn('products', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_featured');
            }
            if (!Schema::hasColumn('products', 'suggested_by')) {
                $table->foreignId('suggested_by')->nullable()->constrained('users')->nullOnDelete()->after('sort_order');
            }
            if (!Schema::hasColumn('products', 'retired_at')) {
                $table->timestamp('retired_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('products', 'retirement_reason')) {
                $table->text('retirement_reason')->nullable()->after('retired_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['is_featured', 'sort_order', 'suggested_by', 'retired_at', 'retirement_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
