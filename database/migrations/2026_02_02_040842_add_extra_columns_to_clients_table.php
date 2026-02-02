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
        Schema::table('clients', function (Blueprint $table) {
            // Re-adding property_name logic if it wasn't successfully migrated before
            if (!Schema::hasColumn('clients', 'property_name')) {
                $table->string('property_name')->nullable()->after('address');
            }
            if (!Schema::hasColumn('clients', 'property_address')) {
                $table->string('property_address')->nullable()->after('property_name');
            }
            if (!Schema::hasColumn('clients', 'property_notes')) {
                $table->text('property_notes')->nullable()->after('property_address');
            }

            // New Specific Fields requested
            if (!Schema::hasColumn('clients', 'property_type')) {
                $table->string('property_type')->nullable()->after('property_notes');
            }
            if (!Schema::hasColumn('clients', 'secondary_email')) {
                $table->string('secondary_email')->nullable()->after('email');
            }
            if (!Schema::hasColumn('clients', 'secondary_phone')) {
                $table->string('secondary_phone')->nullable()->after('phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'property_name',
                'property_address',
                'property_notes',
                'property_type',
                'secondary_email',
                'secondary_phone'
            ]);
        });
    }
};
