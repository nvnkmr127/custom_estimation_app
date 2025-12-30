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
        Schema::table('estimates', function (Blueprint $table) {
            $table->foreignId('approval_chain_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('approval_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['approval_chain_id']);
            $table->dropColumn(['approval_chain_id', 'approval_status']);
        });
    }
};
