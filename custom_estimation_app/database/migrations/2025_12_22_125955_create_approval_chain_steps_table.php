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
        Schema::create('approval_chain_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_chain_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // e.g., 'sales_manager', 'estimator_admin'
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // optional specific user
            $table->integer('order')->default(0); // step sequence
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_chain_steps');
    }
};
