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
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->string('estimate_number')->unique();
            $table->string('title')->nullable();
            $table->integer('client_id')->comment('Perfex Client ID'); // External ID from Perfex
            $table->integer('lead_id')->nullable()->comment('Perfex Lead ID');
            $table->date('estimate_date');
            $table->date('expiry_date')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->enum('status', ['draft', 'sent', 'accepted', 'declined', 'expired'])->default('draft');
            $table->enum('type', ['standard', 'room_based'])->default('standard');

            // Financials
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->string('discount_type')->default('percentage'); // percentage or fixed
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->text('client_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('terms')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
