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
        Schema::table('estimate_approvals', function (Blueprint $table) {
            // Contextual tracking for multi-level approvals
            $table->integer('order')->nullable()->after('user_id');
            $table->unsignedBigInteger('approval_chain_step_id')->nullable()->after('order');
            
            // Add foreign key for integrity (optional but recommended)
            if (Schema::hasTable('approval_chain_steps')) {
                $table->foreign('approval_chain_step_id')
                    ->references('id')
                    ->on('approval_chain_steps')
                    ->nullOnDelete();
            }

            // Index for faster lookups during workflow advancement
            $table->index(['estimate_id', 'status', 'order'], 'idx_est_app_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimate_approvals', function (Blueprint $table) {
            $table->dropIndex('idx_est_app_progress');
            $table->dropForeign(['approval_chain_step_id']);
            $table->dropColumn(['order', 'approval_chain_step_id']);
        });
    }
};
