<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Estimate;
use App\Core\Events\Estimates\EstimateCreated;
use App\Core\Events\Estimates\EstimateUpdated;
use App\Core\Events\Comments\CommentAdded;
use App\Core\Events\Users\UserLoggedIn;

class EventIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        // Create user with necessary roles
        $this->user = User::factory()->create([
            'role' => 'estimator_admin'
        ]);
        $this->client = Client::factory()->create();
    }

    public function test_estimate_creation_dispatches_event()
    {
        // $this->withoutExceptionHandling(); // Uncomment to see full trace
        Event::fake([EstimateCreated::class]);

        $this->actingAs($this->user);

        $response = $this->post(route('estimates.store'), [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'USD',
            'discount_type' => 'fixed',
            'type' => 'standard',
        ]);

        if ($response->exception) {
            dump($response->exception->getMessage());
        }
        $response->assertSessionHasNoErrors();

        Event::assertDispatched(EstimateCreated::class, function ($event) {
            return isset($event->snapshot)
                && $event->getPayload()['estimate_id'] === $event->estimate->id
                && isset($event->getPayload()['snapshot']);
        });
    }

    public function test_estimate_update_dispatches_event()
    {
        Event::fake([EstimateUpdated::class]);

        $this->actingAs($this->user);
        $estimate = Estimate::create([
            'estimate_number' => 'EST-TEST-001',
            'created_by' => $this->user->id,
            'client_id' => $this->client->id,
            'estimate_date' => now(),
            'status' => 'draft',
            'currency' => 'USD',
            'type' => 'standard',
            'discount_type' => 'fixed',
            'grand_total' => 0
        ]);

        $response = $this->put(route('estimates.update', $estimate), [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'status' => 'sent',
            'currency' => 'USD',
            'discount_type' => 'fixed',
            'type' => 'standard'
        ]);

        if ($response->exception) {
            dump($response->exception->getMessage());
        }
        $response->assertSessionHasNoErrors();

        Event::assertDispatched(EstimateUpdated::class, function ($event) {
            return isset($event->snapshot)
                && isset($event->getPayload()['snapshot'])
                && $event->getPayload()['changes']['status'] === 'sent';
        });
    }

    public function test_comment_added_dispatches_event()
    {
        Event::fake([CommentAdded::class]);
        $this->actingAs($this->user);

        $estimate = Estimate::create([
            'estimate_number' => 'EST-TEST-002',
            'created_by' => $this->user->id,
            'client_id' => $this->client->id,
            'estimate_date' => now(),
            'status' => 'draft',
            'currency' => 'USD',
            'type' => 'standard',
            'discount_type' => 'fixed',
            'grand_total' => 0
        ]);

        $response = $this->post(route('comments.store', $estimate), [
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'comment' => 'Test comment',
            'type' => 'internal'
        ]);

        if ($response->exception) {
            dump($response->exception->getMessage());
        }
        $response->assertSessionHasNoErrors();

        Event::assertDispatched(CommentAdded::class);
    }

    public function test_user_login_dispatches_event()
    {
        Event::fake([UserLoggedIn::class]);

        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Event::assertDispatched(UserLoggedIn::class);
    }

    public function test_listeners_are_attached()
    {
        Event::fake();

        // Create a dummy estimate for the event
        $estimate = Estimate::factory()->create();

        $event = new EstimateCreated($estimate, 1);

        // We dispatch using the dispatcher helper or facade
        event($event);

        Event::assertListening(EstimateCreated::class, \App\Listeners\AuditListener::class);
        Event::assertListening(EstimateCreated::class, \App\Listeners\MailListener::class);
        Event::assertListening(EstimateCreated::class, \App\Listeners\WebhookListener::class);
        Event::assertListening(EstimateCreated::class, \App\Listeners\NotificationListener::class);
    }
}
