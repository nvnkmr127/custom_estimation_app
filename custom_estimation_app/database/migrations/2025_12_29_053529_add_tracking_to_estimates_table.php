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
            if (!Schema::hasColumn('estimates', 'view_count')) {
                $table->integer('view_count')->default(0);
            }
            if (!Schema::hasColumn('estimates', 'nudge_task_created')) {
                $table->boolean('nudge_task_created')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (Schema::hasColumn('estimates', 'view_count')) {
                $table->dropColumn('view_count');
            }
            if (Schema::hasColumn('estimates', 'nudge_task_created')) {
                $table->dropColumn('nudge_task_created');
            }
        });
    }
};
