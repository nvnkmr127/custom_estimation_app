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
        // Fix estimate_status='active' (legacy) → map using old 'status' column
        // Maps: waiting_approval → pending_approval, otherwise use a direct match
        $legacyStatusMap = [
            'draft' => 'draft',
            'waiting_approval' => 'pending_approval',
            'approved' => 'approved',
            'sent' => 'sent',
            'accepted' => 'accepted',
            'declined' => 'declined',
        ];

        foreach ($legacyStatusMap as $legacyStatus => $newEstimateStatus) {
            DB::table('estimates')
                ->where('estimate_status', 'active')
                ->where('status', $legacyStatus)
                ->update(['estimate_status' => $newEstimateStatus]);
        }

        // Safety net: any remaining 'active' → draft
        DB::table('estimates')
            ->where('estimate_status', 'active')
            ->update(['estimate_status' => 'draft']);

        // Fix approval_status legacy values
        DB::table('estimates')
            ->where('approval_status', 'draft')
            ->update(['approval_status' => 'not_required']);

        DB::table('estimates')
            ->whereIn('approval_status', ['submitted', 'pending'])
            ->update(['approval_status' => 'waiting']);

        // Fix client_status legacy values
        DB::table('estimates')
            ->where('client_status', 'draft')
            ->update(['client_status' => 'not_sent']);

        // Fix estimate_status consistency with approval_status
        // If still in 'draft' estimate_status but approval is 'waiting' → should be 'pending_approval'
        DB::table('estimates')
            ->where('estimate_status', 'draft')
            ->where('approval_status', 'waiting')
            ->update(['estimate_status' => 'pending_approval']);

        // If still in 'draft' estimate_status but approval is 'approved' → should be 'approved'
        DB::table('estimates')
            ->where('estimate_status', 'draft')
            ->where('approval_status', 'approved')
            ->update(['estimate_status' => 'approved']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM('not_required', 'waiting', 'approved', 'changes_requested', 'rejected') DEFAULT 'not_required'");
            DB::statement("ALTER TABLE estimates MODIFY COLUMN client_status VARCHAR(255) DEFAULT 'not_sent'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM('draft', 'submitted', 'pending', 'approved', 'rejected', 'changes_requested') DEFAULT 'draft'");
            DB::statement("ALTER TABLE estimates MODIFY COLUMN client_status VARCHAR(255) DEFAULT 'draft'");
        }

        // Reverse data if possible? Note that not_sent -> draft is one-way, but we do best effort
        DB::table('estimates')
            ->where('approval_status', 'not_required')
            ->update(['approval_status' => 'draft']);

        DB::table('estimates')
            ->where('approval_status', 'waiting')
            ->update(['approval_status' => 'pending']);

        DB::table('estimates')
            ->where('client_status', 'not_sent')
            ->update(['client_status' => 'draft']);
    }
};
