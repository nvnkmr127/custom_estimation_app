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
        Schema::create('automation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // e.g., 'Marketing', 'Sales', 'Data'
            $table->boolean('is_system')->default(false); // System templates cannot be deleted by users
            $table->json('structure'); // The full JSON dump of the automation structure (triggers, steps, etc.)
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('category');
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_templates');
    }
};
