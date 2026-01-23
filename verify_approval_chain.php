<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Checking ApprovalChain model...\n";
    $chain = \App\Models\ApprovalChain::first();
    if ($chain) {
        echo "Found chain ID: {$chain->id}\n";
        echo "Attempting to load approvers...\n";
        $chain->load('approvers');
        echo "Approvers loaded successfully. Count: " . $chain->approvers->count() . "\n";
        
        echo "Attempting to access checklists property...\n";
        try {
            $checklists = $chain->checklists;
            echo "Checklists accessed. Count: " . $checklists->count() . "\n";
        } catch (\Exception $e) {
            echo "Failed to access checklists: " . $e->getMessage() . "\n";
        }
    } else {
        echo "No ApprovalChain found.\n";
    }

    echo "\nChecking Estimate with ApprovalChain...\n";
    $estimate = \App\Models\Estimate::whereNotNull('approval_chain_id')->first();
    if ($estimate) {
        echo "Found estimate ID: {$estimate->id} with chain ID: {$estimate->approval_chain_id}\n";
        echo "Attempting eager load 'approvalChain.approvers'...\n";
        $e = \App\Models\Estimate::with('approvalChain.approvers')->find($estimate->id);
        echo "Eager load successful.\n";
        
        echo "Attempting eager load 'checklistItems.checklist'...\n";
         $e2 = \App\Models\Estimate::with('checklistItems.checklist')->find($estimate->id);
        echo "Eager load checklistItems.checklist successful.\n";
    } else {
        echo "No estimate with approval chain found.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
