<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\User;
use App\Notifications\EstimateNurtureNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContentTokenTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_replaces_smart_tokens_in_notifications()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'name' => 'John Doe',
        ]);

        $expiryDate = now()->addDays(5);

        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'estimate_number' => 'EST-TOKEN-001',
            'grand_total' => 1234.56,
            'expiry_date' => $expiryDate,
        ]);

        $notification = new EstimateNurtureNotification($estimate, [
            'subject' => 'Hi {{ client_first_name }} - Estimate {{ estimate_number }}',
            'body_lines' => [
                'We prepared an estimate for {{ client_name }}.',
                'Total: ${{ estimate_total }}',
                'Expires on: {{ estimate_expiry }}',
                'Days left: {{ days_until_expiry }}',
            ]
        ]);

        $mail = $notification->toMail($user);

        // Assert Subject
        $this->assertEquals('Hi John - Estimate EST-TOKEN-001', $mail->subject);

        // Assert Lines
        $this->assertStringContainsString('We prepared an estimate for John Doe.', $mail->introLines[0]);
        $this->assertStringContainsString('Total: $1,234.56', $mail->introLines[1]);
        $this->assertStringContainsString('Expires on: ' . $expiryDate->format('M d, Y'), $mail->introLines[2]);

        // Days Left might be 4 or 5 depending on time, but ensuring it's a number is good enough or strictly calc
        // now()->diffInDays(now()->addDays(5)) -> 5.
        // Or 4 if time difference is slight? "startOfDay" logic implies 5.
        // Let's assert strictly if consistent.
        $this->assertStringContainsString('Days left: 5', $mail->introLines[3]);
    }
}
