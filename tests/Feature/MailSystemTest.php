<?php

namespace Tests\Feature;

use App\Core\Events\Approvals\ApprovalRequested;
use App\Listeners\MailListener;
use App\Models\Brand;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Estimate;
use App\Models\User;
use App\Services\Mail\EmailDispatcher;
use App\Services\Mail\Contracts\MailGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MailSystemTest extends TestCase
{
    use RefreshDatabase;

    private array $sentEmails = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'queue.default' => 'database',
            'queue.connections.database.after_commit' => false,
        ]);
    }

    private function bindFakeGateway(): void
    {
        $this->sentEmails = [];

        $gateway = \Mockery::mock(MailGatewayInterface::class);
        $gateway->shouldReceive('send')->andReturnUsing(function ($to, $subject, $body) {
            $this->sentEmails[] = compact('to', 'subject', 'body');
            return true;
        });
        $this->app->instance(MailGatewayInterface::class, $gateway);
    }

    private function assertMailQueuedOrSent(array $payloadNeedles, ?callable $sentMatcher = null): void
    {
        $payloads = DB::table('jobs')->pluck('payload');

        $queued = $payloads->contains(function ($payload) use ($payloadNeedles) {
            $haystack = $payload;

            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $command = data_get($decoded, 'data.command');
                if (is_string($command)) {
                    $job = @unserialize($command);
                    if ($job === false) {
                        $job = @unserialize(base64_decode($command, true) ?: '');
                    }
                    if (is_object($job)) {
                        $to = property_exists($job, 'to') ? (string) $job->to : '';
                        $subject = property_exists($job, 'subject') ? (string) $job->subject : '';
                        $body = property_exists($job, 'body') ? (string) $job->body : '';
                        $haystack = $to . "\n" . $subject . "\n" . $body;
                    }
                }
            }

            foreach ($payloadNeedles as $needle) {
                if (!str_contains($haystack, $needle)) {
                    return false;
                }
            }
            return true;
        });

        if ($queued) {
            $this->assertTrue($queued);
            return;
        }

        if ($sentMatcher) {
            $this->assertTrue(collect($this->sentEmails)->contains($sentMatcher));
            return;
        }

        $this->assertNotEmpty($this->sentEmails);
    }

    public function test_email_dispatcher_dispatches_job()
    {
        $this->bindFakeGateway();

        $dispatcher = app(EmailDispatcher::class);

        $dispatcher->dispatch(
            'test@example.com',
            'Test Subject',
            'emails.welcome',
            ['name' => 'Test User']
        );

        $this->assertMailQueuedOrSent(
            ['test@example.com', 'Test Subject'],
            function ($m) {
                return $m['to'] === 'test@example.com'
                    && $m['subject'] === 'Test Subject'
                    && str_contains($m['body'], 'Welcome to');
            }
        );
    }

    public function test_user_registration_dispatches_email()
    {
        $this->bindFakeGateway();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $event = new \App\Core\Events\Users\UserRegistered($user->id, $user->email);
        $listener = app(MailListener::class);
        $listener->handle($event);

        $this->assertMailQueuedOrSent(
            ['test@example.com'],
            function ($m) {
                return $m['to'] === 'test@example.com' && str_contains($m['body'], 'Test User');
            }
        );
    }

    public function test_estimate_sent_to_client_dispatches_email()
    {
        $this->bindFakeGateway();

        $client = Client::factory()->create(['email' => 'client@example.com', 'name' => 'Client X']);
        $sender = User::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'created_by' => $sender->id,
            'estimate_number' => 'EST-001',
            'grand_total' => 1000,
            'estimate_status' => Estimate::EST_STATUS_APPROVED,
            'client_status' => Estimate::CLT_STATUS_NOT_SENT,
            'is_current_version' => true,
            'expires_at' => now()->addDays(7),
            'currency' => 'USD',
        ]);

        $event = new \App\Core\Events\Estimates\EstimateSent($estimate, $sender->id, 'email');
        $listener = app(MailListener::class);
        $listener->handle($event);

        $this->assertMailQueuedOrSent(
            ['client@example.com', 'EST-001'],
            function ($m) {
                return $m['to'] === 'client@example.com'
                    && str_contains($m['subject'], 'EST-001')
                    && str_contains($m['body'], '1,000.00');
            }
        );
    }

    public function test_approval_requested_dispatches_email()
    {
        $this->bindFakeGateway();

        $user = User::factory()->create();
        $approver = User::factory()->create(['email' => 'approver@example.com']);
        $estimate = Estimate::factory()->create([
            'created_by' => $user->id,
            'estimate_number' => 'EST-APPR-001',
            'grand_total' => 5000,
        ]);

        // Simulating the event directly
        $approval = \App\Models\EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $approver->id,
            'status' => 'pending',
        ]);
        $event = new ApprovalRequested($estimate->id, $approval, $approver->id);

        $listener = app(MailListener::class);
        $listener->handle($event);

        $this->assertMailQueuedOrSent(
            ['approver@example.com', 'EST-APPR-001'],
            function ($m) {
                return $m['to'] === 'approver@example.com'
                    && str_contains($m['subject'], 'Action Required')
                    && str_contains($m['body'], 'EST-APPR-001');
            }
        );
    }

    public function test_db_template_overrides_file_view()
    {
        $this->bindFakeGateway();

        // Create a DB template that matches the welcome email code
        EmailTemplate::create([
            'code' => 'emails.welcome',
            'name' => 'Custom Welcome',
            'subject' => 'Custom Subject',
            'body_html' => '<h1>Hello {{ $name }}</h1><p>Running on DB Template</p>',
        ]);

        $dispatcher = app(EmailDispatcher::class);
        $dispatcher->dispatch('test@example.com', 'Welcome', 'emails.welcome', ['name' => 'DB User']);

        $this->assertMailQueuedOrSent(
            ['test@example.com', 'Running on DB Template', 'DB User'],
            function ($m) {
                return $m['to'] === 'test@example.com'
                    && str_contains($m['body'], 'Running on DB Template')
                    && str_contains($m['body'], 'Hello DB User');
            }
        );
    }

    public function test_branding_injection()
    {
        $this->bindFakeGateway();

        Brand::create([
            'name' => 'Acme Corp',
            'primary_color' => '#ff0000',
            'is_default' => true,
        ]);

        EmailTemplate::create([
            'code' => 'branding.test',
            'name' => 'Branding Test',
            'subject' => 'Branding',
            'body_html' => '<div style="color: {{ $brand_color_primary }}">{{ $brand_name }}</div>',
        ]);

        $dispatcher = app(EmailDispatcher::class);
        $dispatcher->dispatch('test@example.com', 'Branding', 'branding.test', []);

        $this->assertMailQueuedOrSent(
            ['test@example.com', '#ff0000', 'Acme Corp'],
            function ($m) {
                return $m['to'] === 'test@example.com'
                    && str_contains($m['body'], '#ff0000')
                    && str_contains($m['body'], 'Acme Corp');
            }
        );
    }

    public function test_estimate_viewed_dispatches_email()
    {
        $this->bindFakeGateway();

        $creator = User::factory()->create(['email' => 'creator@example.com']);
        $estimate = Estimate::factory()->create([
            'created_by' => $creator->id,
            'estimate_number' => 'EST-VIEW-01',
            'client_id' => Client::factory()->create(['name' => 'Client X'])->id,
        ]);

        // Fire event
        $event = new \App\Core\Events\Estimates\EstimateViewed($estimate, null, '127.0.0.1');
        $listener = app(MailListener::class);
        $listener->handle($event);

        $this->assertMailQueuedOrSent(
            ['creator@example.com', '127.0.0.1', 'Client X'],
            function ($m) {
                return $m['to'] === 'creator@example.com'
                    && str_contains($m['subject'], 'Estimate Viewed')
                    && str_contains($m['body'], 'Client X')
                    && str_contains($m['body'], '127.0.0.1');
            }
        );
    }

    public function test_comment_notification_dispatches_email()
    {
        $this->bindFakeGateway();

        $creator = User::factory()->create(['email' => 'creator@example.com']);
        $estimate = Estimate::factory()->create([
            'created_by' => $creator->id,
            'estimate_number' => 'EST-COMM-01',
        ]);

        $comment = \App\Models\EstimateComment::create([
            'estimate_id' => $estimate->id,
            'commentable_type' => \App\Models\Estimate::class,
            'commentable_id' => $estimate->id,
            'comment' => 'This is a client comment',
            'type' => 'client',
            'client_name' => 'John Doe'
        ]);

        // Fire event
        $event = new \App\Core\Events\Comments\CommentAdded(
            $comment->id,
            $estimate->id,
            null, // authorUserId null for client
            'This is a client comment'
        );
        $listener = app(MailListener::class);
        $listener->handle($event);

        $this->assertMailQueuedOrSent(
            [$creator->email, 'John Doe', 'This is a client comment'],
            function ($m) use ($creator) {
                return $m['to'] === $creator->email
                    && str_contains($m['subject'], 'New Comment')
                    && str_contains($m['body'], 'John Doe')
                    && str_contains($m['body'], 'This is a client comment');
            }
        );
    }
}
