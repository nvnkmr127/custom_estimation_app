<?php

namespace Database\Seeders;

use App\Models\ApprovalChain;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder creates test estimates with approval chains
        // Note: Clients are stored as simple IDs in this system

        // Get or create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
            ]
        );

        // Get the first approval chain
        $approvalChain = ApprovalChain::first();

        if (! $approvalChain) {
            $this->command->warn('No approval chains found. Run ApprovalChainSeeder first.');

            return;
        }

        // Create test estimates with approval workflow
        $estimate1 = Estimate::create([
            'estimate_number' => 'EST-'.str_pad(1, 4, '0', STR_PAD_LEFT),
            'title' => 'Office Renovation - Approval Test',
            'client_id' => 1,
            'lead_id' => null,
            'estimate_date' => now(),
            'expiry_date' => now()->addDays(30),
            'currency' => 'USD',
            'status' => 'draft',
            'type' => 'standard',
            'subtotal' => 5000.00,
            'total_tax' => 500.00,
            'discount_total' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'grand_total' => 5500.00,
            'client_note' => 'Test estimate for approval workflow',
            'approval_chain_id' => $approvalChain->id,
            'approval_status' => 'draft',
        ]);

        $estimate2 = Estimate::create([
            'estimate_number' => 'EST-'.str_pad(2, 4, '0', STR_PAD_LEFT),
            'title' => 'Kitchen Remodel - Submitted',
            'client_id' => 2,
            'lead_id' => null,
            'estimate_date' => now(),
            'expiry_date' => now()->addDays(30),
            'currency' => 'USD',
            'status' => 'draft',
            'type' => 'room_based',
            'subtotal' => 15000.00,
            'total_tax' => 1500.00,
            'discount_total' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'grand_total' => 16500.00,
            'client_note' => 'Test estimate already submitted for approval',
            'approval_chain_id' => $approvalChain->id,
            'approval_status' => 'submitted',
        ]);

        // Create approval record for estimate2
        \App\Models\EstimateApproval::create([
            'estimate_id' => $estimate2->id,
            'user_id' => $admin->id,
            'status' => 'pending',
        ]);

        $this->command->info('Created 2 test estimates with approval chains');
        $this->command->info('Estimate 1: Draft status, ready to submit');
        $this->command->info('Estimate 2: Submitted status, pending approval from admin');
    }
}
