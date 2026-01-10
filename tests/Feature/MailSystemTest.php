<?php

namespace Tests\Feature;

use App\Core\Events\Approvals\ApprovalRequested;
use App\Jobs\SendEmailJob;
use App\Listeners\MailListener;
use App\Models\Brand;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Estimate;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\Mail\EmailDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_dispatcher_dispatches_job()
    {
        Queue::fake([SendEmailJob::class]);

        $dispatcher = app(EmailDispatcher::class);

        $dispatcher->dispatch(
            'test@example.com',
            'Test Subject',
            'emails.welcome',
            ['name' => 'Test User']
        );

        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return $job->to === 'test@example.com' &&
                $job->subject === 'Test Subject' &&
                str_contains($job->body, 'Welcome to');
        });
    }

    public function test_user_registration_dispatches_email()
    {
        Queue::fake([SendEmailJob::class]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return $job->to === 'test@example.com' &&
                str_contains($job->body, 'Test User');
        });
    }

    public function test_estimate_sent_to_client_dispatches_email()
    {
        Queue::fake([SendEmailJob::class]);

        // Create Client and Estimate
        $client = Client::factory()->create(['email' => 'client@example.com']);
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'created_by' => User::factory()->create()->id, // helper user
            'estimate_number' => 'EST-001',
            'grand_total' => 1000,
            'status' => 'draft'
        ]);

        $service = app(EstimateService::class);
        $service->sendToClient($estimate);

        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return $job->to === 'client@example.com' &&
                str_contains($job->subject, 'EST-001') &&
                str_contains($job->body, '1,000.00');
        });

        $this->assertEquals('sent', $estimate->fresh()->status);
    }

    public function test_approval_requested_dispatches_email()
    {
        Queue::fake([SendEmailJob::class]);

        $user = User::factory()->create();
        $approver = User::factory()->create(['email' => 'approver@example.com']);
        $estimate = Estimate::factory()->create([
            'created_by' => $user->id,
            'estimate_number' => 'EST-APPR-001',
            'grand_total' => 5000,
        ]);

        // Simulating the event directly
        $event = new ApprovalRequested($estimate->id, 123, $approver->id); // 123 is mock approval ID

        $listener = app(MailListener::class);
        $listener->handle($event);

        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return $job->to === 'approver@example.com' &&
                str_contains($job->subject, 'Action Required') &&
                str_contains($job->body, 'EST-APPR-001');
        });
    }

    public function test_db_template_overrides_file_view()
    {
        Queue::fake([SendEmailJob::class]);

        // Create a DB template that matches the welcome email code
        EmailTemplate::create([
            'code' => 'emails.welcome',
            'name' => 'Custom Welcome',
            'subject' => 'Custom Subject',
            'body_html' => '<h1>Hello {{ $name }}</h1><p>Running on DB Template</p>',
        ]);

        $dispatcher = app(EmailDispatcher::class);
        $dispatcher->dispatch('test@example.com', 'Welcome', 'emails.welcome', ['name' => 'DB User']);

        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return str_contains($job->body, 'Running on DB Template') &&
                str_contains($job->body, 'Hello DB User');
        });
    }

    public function test_branding_injection()
    {
        Queue::fake([SendEmailJob::class]);

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

        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return str_contains($job->body, '#ff0000') &&
                str_contains($job->body, 'Acme Corp');
        });
    }

    public function test_estimate_viewed_dispatches_email()
    {
        Queue::fake([SendEmailJob::class]);

        $creator = User::factory()->create(['email' => 'creator@example.com']);
        $estimate = Estimate::factory()->create([
            'created_by' => $creator->id,
            'estimate_number' => 'EST-VIEW-01',
            'client_id' => Client::factory()->create(['name' => 'Client X'])->id,
        ]);

        // Fire event
        $event = new \App\Core\Events\Estimates\EstimateViewed($estimate->id, null, '127.0.0.1');
        $listener = app(MailListener::class);
        $listener->handle($event);

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($creator) {
            return $job->to === 'creator@example.com' &&
                str_contains($job->subject, 'Estimate Viewed') &&
                str_contains($job->body, 'Client X') &&
                str_contains($job->body, '127.0.0.1');
        });
    }

    public function test_comment_notification_dispatches_email()
    {
        Queue::fake([SendEmailJob::class]);

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

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($creator) {
            return $job->to === $creator->email &&
                str_contains($job->subject, 'New Comment') &&
                str_contains($job->body, 'John Doe') &&
                str_contains($job->body, 'This is a client comment');
        });
    }
}
