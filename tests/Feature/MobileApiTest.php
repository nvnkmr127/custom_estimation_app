<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DeviceToken;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Reminder;
use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_json_response_when_requested()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'estimator',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'user' => [
                'id',
                'name',
                'email',
            ],
            'token',
        ]);
    }

    public function test_register_device_token_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);

        $response = $this->actingAs($user)->postJson('/devices/register', [
            'token' => 'ExponentPushToken[1234567890123456789012]',
            'platform' => 'android',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Device token registered successfully.',
        ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[1234567890123456789012]',
            'platform' => 'android',
        ]);
    }

    public function test_deregister_device_token_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $token = DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[1234567890123456789012]',
            'platform' => 'android',
        ]);

        $response = $this->actingAs($user)->postJson('/devices/deregister', [
            'token' => 'ExponentPushToken[1234567890123456789012]',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Device token deregistered successfully.',
        ]);

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'ExponentPushToken[1234567890123456789012]',
        ]);
    }

    public function test_list_estimates_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson('/estimates');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'estimate_number',
                    'client_id',
                    'status',
                    'total_amount',
                    'expiry_date',
                    'created_by',
                ]
            ]
        ]);
    }

    public function test_get_estimate_by_id()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson("/estimates/{$estimate->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'estimate_number',
            'client_id',
            'status',
            'total_amount',
            'expiry_date',
            'created_by',
            'created_at',
            'sections',
        ]);
    }

    public function test_delete_estimate_successfully()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/estimates/{$estimate->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Estimate deleted successfully.',
        ]);
    }

    public function test_copy_estimate_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/estimates/{$estimate->id}/copy");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'new_estimate_id',
            'message',
        ]);
    }

    public function test_send_estimate_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
            'estimate_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->postJson("/estimates/{$estimate->id}/send");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Estimate sent to client successfully.',
        ]);
    }

    public function test_mark_estimate_status_successfully()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
            'estimate_status' => 'draft',
        ]);

        $response = $this->actingAs($user)->postJson("/estimates/{$estimate->id}/mark-as/pending_approval");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'new_status' => 'pending_approval',
        ]);
    }

    public function test_submit_estimate_for_approval_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $client = Client::factory()->create();
        
        $chain = ApprovalChain::create([
            'name' => 'Test Chain',
            'is_active' => true,
            'is_default' => true,
        ]);
        
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
            'approval_chain_id' => $chain->id,
            'estimate_status' => 'draft',
            'approval_status' => 'not_required',
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name' => 'Line Item',
            'unit_price' => 100,
            'quantity' => 1,
            'unit_type' => 'nos',
            'total' => 100,
            'order_index' => 0,
        ]);

        $response = $this->actingAs($user)->postJson("/estimates/{$estimate->id}/submit");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_approve_estimate_step_successfully()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
            'estimate_status' => 'pending_approval',
            'approval_status' => 'waiting',
            'lock_version' => 1,
        ]);

        \App\Models\EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'order' => 1,
            'snapshot_version' => 1,
        ]);

        $response = $this->actingAs($user)->postJson("/estimates/{$estimate->id}/approve", [
            'comments' => 'Looks great!',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_reject_estimate_successfully()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
            'estimate_status' => 'pending_approval',
            'approval_status' => 'waiting',
            'lock_version' => 1,
        ]);

        \App\Models\EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'order' => 1,
            'snapshot_version' => 1,
        ]);

        $response = $this->actingAs($user)->postJson("/estimates/{$estimate->id}/reject", [
            'reason' => 'Margin is too low. Please raise item price.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Estimate rejected.',
        ]);
    }

    public function test_post_comment_mapping()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/estimates/{$estimate->id}/comments", [
            'content' => 'Yes, it is within the allowed limits.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'comment' => [
                'content' => 'Yes, it is within the allowed limits.',
            ]
        ]);
    }

    public function test_list_clients_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        Client::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/clients');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                ]
            ]
        ]);
    }

    public function test_products_suggest_ai_successfully()
    {
        // Mock OpenAI facade
        \OpenAI\Laravel\Facades\OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'suggested_description' => 'Premium Wood Dining Table description.',
                                'suggested_category' => 'Furniture'
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create(['role' => 'estimator']);

        $response = $this->actingAs($user)->postJson('/products/suggest', [
            'name' => 'Teak Wood Dining Table',
            'attributes' => ['6-seater', 'rustic finish'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'suggested_description' => 'Premium Wood Dining Table description.',
            'suggested_category' => 'Furniture',
        ]);
    }

    public function test_list_reminders_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
        ]);
        
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Follow up with client',
            'remindable_type' => Estimate::class,
            'remindable_id' => $estimate->id,
            'remind_at' => now()->addDay(),
            'type' => 'in_app',
            'is_sent' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/reminders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'due_date',
                'is_read',
            ]
        ]);
    }

    public function test_signed_portal_accept_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
            'estimate_status' => 'sent',
            'client_status' => 'sent',
            'expires_at' => now()->addDays(7),
        ]);

        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'portal.accept',
            now()->addDays(7),
            ['estimate' => $estimate->id]
        );

        $response = $this->postJson($signedUrl, [
            'client_name' => 'Oliver Twist',
            'signature_data' => 'data:image/png;base64,iVBORw0KGgo...',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Estimate accepted successfully.',
        ]);
    }

    public function test_signed_portal_decline_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
            'estimate_status' => 'sent',
            'client_status' => 'sent',
            'expires_at' => now()->addDays(7),
        ]);

        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'portal.decline',
            now()->addDays(7),
            ['estimate' => $estimate->id]
        );

        $response = $this->postJson($signedUrl, [
            'reason' => 'budget',
            'comments' => 'The price exceeds our budget allocation.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Estimate decline registered.',
        ]);
    }
}
