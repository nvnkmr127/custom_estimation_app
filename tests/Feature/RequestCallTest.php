<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use App\Notifications\HotLeadAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RequestCallTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function client_can_request_a_call_using_signed_url()
    {
        Notification::fake();

        // Arrange
        $creator = User::factory()->create();
        $estimate = Estimate::factory()->create([
            'created_by' => $creator->id,
            'status' => 'sent'
        ]);

        // Act: Make a POST request with signed URL
        $url = URL::signedRoute('portal.request-call', $estimate);
        $response = $this->post($url);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check Notification sent to creator
        Notification::assertSentTo(
            [$creator],
            HotLeadAlert::class,
            function ($notification, $channels) use ($estimate) {
                return $notification->estimate->id === $estimate->id;
            }
        );
    }

    /** @test */
    public function request_call_fails_with_invalid_signature()
    {
        Notification::fake();

        $estimate = Estimate::factory()->create(['status' => 'sent']);

        // Act: Make a POST request with TAMPERED URL
        $response = $this->post(route('portal.request-call', $estimate)); // Unsigned

        // Assert
        $response->assertStatus(403);
        Notification::assertNothingSent();
    }
}
