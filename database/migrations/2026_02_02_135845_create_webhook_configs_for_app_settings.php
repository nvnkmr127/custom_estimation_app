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
        // We are moving 'estimate_approval_webhook_url' and 'estimate_client_webhook_url' 
        // from general application settings to the dedicated 'webhooks' table.

        // 1. Estimate Approval Webhook
        $approvalUrl = \DB::table('settings')->where('key', 'estimate_approval_webhook_url')->value('value');
        if ($approvalUrl) {
            \DB::table('webhooks')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'Estimate Approval Notification',
                'url' => $approvalUrl,
                'http_method' => 'POST',
                'status' => 'active',
                'events' => json_encode(['estimate.approval_needed']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Create a placeholder disabled one if it didn't exist, so it shows up in the UI
            \DB::table('webhooks')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'Estimate Approval Notification',
                'url' => 'https://example.com/webhook',
                'http_method' => 'POST',
                'status' => 'inactive',
                'events' => json_encode(['estimate.approval_needed']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Estimate Client Sent Webhook
        $clientUrl = \DB::table('settings')->where('key', 'estimate_client_webhook_url')->value('value');
        if ($clientUrl) {
            \DB::table('webhooks')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'Estimate Sent to Client',
                'url' => $clientUrl,
                'http_method' => 'POST',
                'status' => 'active',
                'events' => json_encode(['estimate.sent']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Create a placeholder disabled one
            \DB::table('webhooks')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'Estimate Sent to Client',
                'url' => 'https://example.com/webhook',
                'http_method' => 'POST',
                'status' => 'inactive',
                'events' => json_encode(['estimate.sent']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Clean up old settings
        \DB::table('settings')->whereIn('key', ['estimate_approval_webhook_url', 'estimate_client_webhook_url'])->delete();
    }

    public function down(): void
    {
        // No strict reverse needed as we are moving to a new system
    }
};
