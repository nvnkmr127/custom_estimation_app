<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Mail\Contracts\MailGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTestEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_masked_password_falls_back_to_stored_smtp_password()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Setting::updateOrCreate(['key' => 'smtp_password'], ['value' => 'real-secret-pw']);

        // Capture the SMTP password config at send time via a shared object holder.
        $holder = new \stdClass();
        $holder->pw = null;
        $this->app->bind(MailGatewayInterface::class, fn () => new class($holder) implements MailGatewayInterface {
            public function __construct(private \stdClass $holder) {}
            public function send(string $to, string $subject, string $body, array $attachments = []): bool
            {
                $this->holder->pw = config('mail.mailers.smtp.password');
                return true;
            }
        });

        $this->actingAs($admin)->postJson(route('settings.test-email'), [
            'email' => 'ops@example.com',
            'smtp_host' => 'smtp.example.com',
            'smtp_username' => 'mailer',
            'smtp_password' => '********', // user left the mask in place
        ])->assertOk();

        $this->assertSame('real-secret-pw', $holder->pw);
    }
}
