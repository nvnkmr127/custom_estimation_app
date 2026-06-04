<?php

namespace Database\Seeders;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalChainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default approval chain
        $chain = ApprovalChain::create([
            'name' => 'Standard Approval',
            'description' => 'Default approval workflow for estimates',
            'is_active' => true,
        ]);

        // Get admin user for approval
        $admin = User::where('role', 'super_admin')->orWhere('role', 'estimator_admin')->first();

        if ($admin) {
            ApprovalChainStep::create([
                'approval_chain_id' => $chain->id,
                'role' => 'super_admin',
                'user_id' => $admin->id,
                'order' => 1,
            ]);
        }

        // Create a two-step approval chain
        $twoStepChain = ApprovalChain::create([
            'name' => 'Two-Step Approval',
            'description' => 'Requires approval from sales manager and admin',
            'is_active' => true,
        ]);

        $salesManager = User::where('role', 'estimator_manager')->first();

        if ($salesManager) {
            ApprovalChainStep::create([
                'approval_chain_id' => $twoStepChain->id,
                'role' => 'estimator_manager',
                'user_id' => $salesManager->id,
                'order' => 1,
            ]);
        }

        if ($admin) {
            ApprovalChainStep::create([
                'approval_chain_id' => $twoStepChain->id,
                'role' => 'super_admin',
                'user_id' => $admin->id,
                'order' => 2,
            ]);
        }
    }
}
