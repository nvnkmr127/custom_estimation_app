<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_forged_offsite_redirect_is_blocked()
    {
        // Attacker-crafted link with an unsigned off-site target must not redirect.
        $this->get('/tracking/click/1?target=' . urlencode('https://evil-phishing.example'))
            ->assertForbidden();
    }

    public function test_same_origin_target_is_allowed_without_signature()
    {
        // Same-origin links (the common case) keep working even without a signature,
        // so previously-sent emails don't break.
        $target = config('app.url') . '/dashboard';
        $this->get('/tracking/click/1?target=' . urlencode($target))
            ->assertRedirect($target);
    }

    public function test_signed_offsite_link_redirects_and_records_click()
    {
        $log = EmailLog::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'recipient_email' => 'client@example.com',
            'subject' => 'Hi',
            'status' => 'sent',
        ]);

        $target = 'https://partner.example.com/landing';
        $signed = URL::signedRoute('tracking.click', ['id' => $log->id, 'target' => $target]);

        $this->get($signed)->assertRedirect($target);

        $this->assertNotEmpty($log->fresh()->click_data);
    }
}
