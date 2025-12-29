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
        // 1. Add columns to products table
        Schema::table('products', function (Blueprint $table) {
            $table->json('dimensions')->nullable()->after('unit_type'); // {length, width, height, unit}
            $table->json('tags')->nullable()->after('dimensions'); // ["tag1", "tag2"]
        });

        // 2. Create product_images table
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // 3. Create product_options table (e.g., Color, Size)
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Material", "Color"
            $table->timestamps();
        });

        // 4. Create product_option_values table (e.g., Wood, Red)
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained('product_options')->cascadeOnDelete();
            $table->string('value'); // e.g. "Wood", "Red", "Large"
            $table->decimal('price_adjustment', 10, 2)->default(0); // +$50.00
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_images');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['dimensions', 'tags']);
        });
    }
};
