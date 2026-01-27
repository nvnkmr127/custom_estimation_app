<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the driver name
        $connection = Schema::connection(null)->getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Change ENUM to VARCHAR to allow all statuses including waiting_approval
            DB::statement("ALTER TABLE estimates MODIFY COLUMN status VARCHAR(50) DEFAULT 'draft'");
        }
        // SQLite doesn't strictly enforce ENUMs so it might be fine, but to be sure:
        // In SQLite, we can't easily modify column type without table recreation.
        // Assuming MySQL/MariaDB for production/local usually.

        // Also ensure tax columns exist (idempotent check)
        if (!Schema::hasColumn('estimates', 'tax_1')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->decimal('tax_1', 5, 2)->default(0)->after('subtotal');
            });
        }
        if (!Schema::hasColumn('estimates', 'tax_2')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->decimal('tax_2', 5, 2)->default(0)->after('tax_1');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
