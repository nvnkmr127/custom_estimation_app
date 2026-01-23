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
        // Add indexes to estimates table for common queries
        Schema::table('estimates', function (Blueprint $table) {
            try {
                $table->index('status');
            } catch (\Exception $e) {
            }
            try {
                $table->index('client_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('created_by');
            } catch (\Exception $e) {
            }
            try {
                $table->index('approval_chain_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('approval_status');
            } catch (\Exception $e) {
            }
            try {
                $table->index('estimate_date');
            } catch (\Exception $e) {
            }
            try {
                $table->index('deleted_at');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to estimate_items table
        Schema::table('estimate_items', function (Blueprint $table) {
            try {
                $table->index('estimate_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('estimate_section_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('product_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('unit_type_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('deleted_at');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to estimate_sections table
        Schema::table('estimate_sections', function (Blueprint $table) {
            try {
                $table->index('estimate_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('deleted_at');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to products table
        Schema::table('products', function (Blueprint $table) {
            try {
                $table->index('category_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('status');
            } catch (\Exception $e) {
            }
            try {
                $table->index('is_featured');
            } catch (\Exception $e) {
            }
            try {
                $table->index('sort_order');
            } catch (\Exception $e) {
            }
            try {
                $table->index('unit_type_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('deleted_at');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to estimate_approvals table
        Schema::table('estimate_approvals', function (Blueprint $table) {
            try {
                $table->index('estimate_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('user_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('status');
            } catch (\Exception $e) {
            }
            try {
                $table->index(['estimate_id', 'user_id', 'status'], 'estimate_approvals_composite_idx');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to estimate_comments table
        Schema::table('estimate_comments', function (Blueprint $table) {
            try {
                $table->index('estimate_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('user_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('status');
            } catch (\Exception $e) {
            }
            try {
                $table->index('is_read');
            } catch (\Exception $e) {
            }
            try {
                $table->index(['commentable_type', 'commentable_id'], 'estimate_comments_commentable_idx');
            } catch (\Exception $e) {
            }
            try {
                $table->index('deleted_at');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to activity_logs table
        Schema::table('activity_logs', function (Blueprint $table) {
            try {
                $table->index(['subject_type', 'subject_id'], 'activity_logs_subject_idx');
            } catch (\Exception $e) {
            }
            try {
                $table->index(['causer_type', 'causer_id'], 'activity_logs_causer_idx');
            } catch (\Exception $e) {
            }
            try {
                $table->index('action');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to product_images table
        Schema::table('product_images', function (Blueprint $table) {
            try {
                $table->index('product_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index('display_order');
            } catch (\Exception $e) {
            }
        });

        // Add indexes to followers table
        Schema::table('followers', function (Blueprint $table) {
            try {
                $table->index('user_id');
            } catch (\Exception $e) {
            }
            try {
                $table->index(['followable_type', 'followable_id'], 'followers_followable_idx');
            } catch (\Exception $e) {
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followers', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex('followers_followable_idx');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['display_order']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_subject_idx');
            $table->dropIndex('activity_logs_causer_idx');
            $table->dropIndex(['action']);
        });

        Schema::table('estimate_comments', function (Blueprint $table) {
            $table->dropIndex(['estimate_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['is_read']);
            $table->dropIndex('estimate_comments_commentable_idx');
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('estimate_approvals', function (Blueprint $table) {
            $table->dropIndex(['estimate_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex('estimate_approvals_composite_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['unit_type_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->dropIndex(['estimate_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropIndex(['estimate_id']);
            $table->dropIndex(['estimate_section_id']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['unit_type_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['client_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['approval_chain_id']);
            $table->dropIndex(['approval_status']);
            $table->dropIndex(['estimate_date']);
            $table->dropIndex(['deleted_at']);
        });
    }
};
