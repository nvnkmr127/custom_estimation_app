<?php

namespace Tests\Feature;

use App\Models\Estimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateAcceptanceStateTest extends TestCase
{
    use RefreshDatabase;

    private function sentEstimate(string $clientStatus): Estimate
    {
        return Estimate::factory()->create([
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'client_status' => $clientStatus,
            'accepted_at' => null,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_can_be_accepted_from_sent_and_viewed()
    {
        $this->assertTrue($this->sentEstimate(Estimate::CLT_STATUS_SENT)->canBeAccepted());
        // VIEWED -> ACCEPTED is allowed by the state machine; must not be blocked
        $this->assertTrue($this->sentEstimate(Estimate::CLT_STATUS_VIEWED)->canBeAccepted());
    }

    public function test_cannot_be_accepted_when_already_accepted_or_expired()
    {
        $accepted = $this->sentEstimate(Estimate::CLT_STATUS_SENT);
        $accepted->accepted_at = now();
        $this->assertFalse($accepted->canBeAccepted());

        $expired = $this->sentEstimate(Estimate::CLT_STATUS_SENT);
        $expired->expires_at = now()->subDay();
        $this->assertFalse($expired->canBeAccepted());
    }
}
