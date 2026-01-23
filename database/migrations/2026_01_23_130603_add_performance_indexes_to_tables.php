<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexes('estimates', [
            ['columns' => 'status', 'name' => 'estimates_status_index'],
            ['columns' => 'client_id', 'name' => 'estimates_client_id_index'],
            ['columns' => 'created_by', 'name' => 'estimates_created_by_index'],
            ['columns' => 'approval_chain_id', 'name' => 'estimates_approval_chain_id_index'],
            ['columns' => 'approval_status', 'name' => 'estimates_approval_status_index'],
            ['columns' => 'estimate_date', 'name' => 'estimates_estimate_date_index'],
            ['columns' => 'deleted_at', 'name' => 'estimates_deleted_at_index'],
        ]);

        $this->addIndexes('estimate_items', [
            ['columns' => 'estimate_id', 'name' => 'estimate_items_estimate_id_index'],
            ['columns' => 'estimate_section_id', 'name' => 'estimate_items_estimate_section_id_index'],
            ['columns' => 'product_id', 'name' => 'estimate_items_product_id_index'],
            ['columns' => 'unit_type_id', 'name' => 'estimate_items_unit_type_id_index'],
            ['columns' => 'deleted_at', 'name' => 'estimate_items_deleted_at_index'],
        ]);

        $this->addIndexes('estimate_sections', [
            ['columns' => 'estimate_id', 'name' => 'estimate_sections_estimate_id_index'],
            ['columns' => 'deleted_at', 'name' => 'estimate_sections_deleted_at_index'],
        ]);

        $this->addIndexes('products', [
            ['columns' => 'category_id', 'name' => 'products_category_id_index'],
            ['columns' => 'status', 'name' => 'products_status_index'],
            ['columns' => 'is_featured', 'name' => 'products_is_featured_index'],
            ['columns' => 'sort_order', 'name' => 'products_sort_order_index'],
            ['columns' => 'unit_type_id', 'name' => 'products_unit_type_id_index'],
            ['columns' => 'deleted_at', 'name' => 'products_deleted_at_index'],
        ]);

        $this->addIndexes('estimate_approvals', [
            ['columns' => 'estimate_id', 'name' => 'estimate_approvals_estimate_id_index'],
            ['columns' => 'user_id', 'name' => 'estimate_approvals_user_id_index'],
            ['columns' => 'status', 'name' => 'estimate_approvals_status_index'],
            ['columns' => ['estimate_id', 'user_id', 'status'], 'name' => 'estimate_approvals_composite_idx'],
        ]);

        $this->addIndexes('estimate_comments', [
            ['columns' => 'estimate_id', 'name' => 'estimate_comments_estimate_id_index'],
            ['columns' => 'user_id', 'name' => 'estimate_comments_user_id_index'],
            ['columns' => 'status', 'name' => 'estimate_comments_status_index'],
            ['columns' => 'is_read', 'name' => 'estimate_comments_is_read_index'],
            ['columns' => ['commentable_type', 'commentable_id'], 'name' => 'estimate_comments_commentable_idx'],
            ['columns' => 'deleted_at', 'name' => 'estimate_comments_deleted_at_index'],
        ]);

        $this->addIndexes('activity_logs', [
            ['columns' => ['subject_type', 'subject_id'], 'name' => 'activity_logs_subject_idx'],
            ['columns' => 'user_id', 'name' => 'activity_logs_user_id_index'],
            ['columns' => 'action', 'name' => 'activity_logs_action_index'],
        ]);

        $this->addIndexes('product_images', [
            ['columns' => 'product_id', 'name' => 'product_images_product_id_index'],
            ['columns' => 'display_order', 'name' => 'product_images_display_order_index'],
        ]);

        $this->addIndexes('followers', [
            ['columns' => 'user_id', 'name' => 'followers_user_id_index'],
            ['columns' => ['followable_type', 'followable_id'], 'name' => 'followers_followable_idx'],
        ]);
    }

    /**
     * Add indexes to a table if they don't exist.
     */
    private function addIndexes(string $table, array $indexes): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes($table))->pluck('name')->toArray();

        foreach ($indexes as $indexData) {
            $name = $indexData['name'];
            $columns = (array) $indexData['columns'];

            // Skip if index already exists
            if (in_array($name, $existingIndexes)) {
                continue;
            }

            // Verify all columns exist
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue 2;
                }
            }

            try {
                Schema::table($table, function (Blueprint $tableObj) use ($columns, $name) {
                    $tableObj->index($columns, $name);
                });
            } catch (\Exception $e) {
                // Fail silently if index cannot be created
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexes('followers', [
            'user_id',
            'followers_followable_idx',
        ]);

        $this->dropIndexes('product_images', [
            'product_id',
            'display_order',
        ]);

        $this->dropIndexes('activity_logs', [
            'activity_logs_subject_idx',
            'user_id',
            'action',
        ]);

        $this->dropIndexes('estimate_comments', [
            'estimate_id',
            'user_id',
            'status',
            'is_read',
            'estimate_comments_commentable_idx',
            'deleted_at',
        ]);

        $this->dropIndexes('estimate_approvals', [
            'estimate_id',
            'user_id',
            'status',
            'estimate_approvals_composite_idx',
        ]);

        $this->dropIndexes('products', [
            'category_id',
            'status',
            'is_featured',
            'sort_order',
            'unit_type_id',
            'deleted_at',
        ]);

        $this->dropIndexes('estimate_sections', [
            'estimate_id',
            'deleted_at',
        ]);

        $this->dropIndexes('estimate_items', [
            'estimate_id',
            'estimate_section_id',
            'product_id',
            'unit_type_id',
            'deleted_at',
        ]);

        $this->dropIndexes('estimates', [
            'status',
            'client_id',
            'created_by',
            'approval_chain_id',
            'approval_status',
            'estimate_date',
            'deleted_at',
        ]);
    }

    /**
     * Drop indexes from a table if they exist.
     */
    private function dropIndexes(string $table, array $indexes): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes($table))->pluck('name')->toArray();

        foreach ($indexes as $index) {
            // Check if index exists by name or by column convention
            $indexName = $index;
            if (!in_array($indexName, $existingIndexes)) {
                // If it's a simple column, Laravel's convention is table_column_index
                $conventionName = $table . '_' . $index . '_index';
                if (in_array($conventionName, $existingIndexes)) {
                    $indexName = $conventionName;
                } else {
                    continue; // Skip if not found
                }
            }

            try {
                Schema::table($table, function (Blueprint $tableObj) use ($indexName) {
                    $tableObj->dropIndex($indexName);
                });
            } catch (\Exception $e) {
                // Fail silently
            }
        }
    }
};
