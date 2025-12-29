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
        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->onDelete('cascade');
            $table->foreignId('estimate_section_id')->nullable()->constrained()->onDelete('cascade'); // Nullable for standard estimates
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null'); // Optional link to product

            $table->string('name'); // Snapshot of product name or custom
            $table->text('description')->nullable();

            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit_type')->default('nos');

            $table->decimal('tax_1', 5, 2)->default(0);
            $table->decimal('tax_2', 5, 2)->default(0);

            $table->decimal('total', 15, 2)->default(0); // (price * qty) + tax
            $table->integer('order_index')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
