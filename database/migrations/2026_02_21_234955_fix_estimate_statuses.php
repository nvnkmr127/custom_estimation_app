<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * IMPORTANT MySQL ENUM rule:
     *   You cannot UPDATE a row to a value that is not already in the ENUM.
     *   Correct order: WIDEN enum → UPDATE data → NARROW enum.
     */
    public function up(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        // ─────────────────────────────────────────────────────────────────────
        // STEP 1 (MySQL only): Widen ENUM to include both old AND new values
        // so that subsequent UPDATE statements don't get truncation errors.
        // ─────────────────────────────────────────────────────────────────────
        if ($isMysql) {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM(
                'draft','submitted','pending',
                'not_required','waiting','approved','changes_requested','rejected'
            ) DEFAULT 'draft'");
        }

        // ─────────────────────────────────────────────────────────────────────
        // STEP 2: Fix estimate_status = 'active' (legacy) using old 'status' col
        // ─────────────────────────────────────────────────────────────────────
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

        // Safety net: any remaining 'active' → 'draft'
        DB::table('estimates')
            ->where('estimate_status', 'active')
            ->update(['estimate_status' => 'draft']);

        // ─────────────────────────────────────────────────────────────────────
        // STEP 3: Migrate approval_status legacy values
        //   'draft'               → 'not_required'
        //   'submitted'/'pending' → 'waiting'
        // ─────────────────────────────────────────────────────────────────────
        DB::table('estimates')
            ->where('approval_status', 'draft')
            ->update(['approval_status' => 'not_required']);

        DB::table('estimates')
            ->whereIn('approval_status', ['submitted', 'pending'])
            ->update(['approval_status' => 'waiting']);

        // ─────────────────────────────────────────────────────────────────────
        // STEP 4: Migrate client_status legacy values
        //   'draft' → 'not_sent'
        // ─────────────────────────────────────────────────────────────────────
        DB::table('estimates')
            ->where('client_status', 'draft')
            ->update(['client_status' => 'not_sent']);

        // ─────────────────────────────────────────────────────────────────────
        // STEP 5: Align estimate_status with approval_status for consistency
        //   estimate_status='draft' + approval_status='waiting'   → 'pending_approval'
        //   estimate_status='draft' + approval_status='approved'  → 'approved'
        // ─────────────────────────────────────────────────────────────────────
        DB::table('estimates')
            ->where('estimate_status', 'draft')
            ->where('approval_status', 'waiting')
            ->update(['estimate_status' => 'pending_approval']);

        DB::table('estimates')
            ->where('estimate_status', 'draft')
            ->where('approval_status', 'approved')
            ->update(['estimate_status' => 'approved']);

        // ─────────────────────────────────────────────────────────────────────
        // STEP 6 (MySQL only): Narrow ENUM to only the new valid values
        // ─────────────────────────────────────────────────────────────────────
        if ($isMysql) {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM(
                'not_required','waiting','approved','changes_requested','rejected'
            ) DEFAULT 'not_required'");

            DB::statement("ALTER TABLE estimates MODIFY COLUMN client_status VARCHAR(255) DEFAULT 'not_sent'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        // Widen first before reverting data (same rule in reverse)
        if ($isMysql) {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM(
                'not_required','waiting','approved','changes_requested','rejected',
                'draft','submitted','pending'
            ) DEFAULT 'not_required'");
        }

        // Revert data
        DB::table('estimates')
            ->where('approval_status', 'not_required')
            ->update(['approval_status' => 'draft']);

        DB::table('estimates')
            ->where('approval_status', 'waiting')
            ->update(['approval_status' => 'pending']);

        DB::table('estimates')
            ->where('client_status', 'not_sent')
            ->update(['client_status' => 'draft']);

        // Narrow back to old ENUM definition
        if ($isMysql) {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN approval_status ENUM(
                'draft','submitted','pending','approved','rejected','changes_requested'
            ) DEFAULT 'draft'");

            DB::statement("ALTER TABLE estimates MODIFY COLUMN client_status VARCHAR(255) DEFAULT 'draft'");
        }
    }
};
