<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

class GenerativeAITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock OpenAI facade
        OpenAI::fake([
            Chat::class => [
                'create' => \OpenAI\Responses\Chat\CreateResponse::fake([
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'This is a generated description.',
                            ],
                        ],
                    ],
                ]),
            ],
        ]);
    }

    public function test_ai_generation_endpoint_works()
    {
        // Mocking OpenAI
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is a generated description.',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('ai.generate'), [
                'name' => 'Test Product',
                'attributes' => ['Material' => 'Wood'],
            ]);

        $response->assertStatus(200)
            ->assertJson(['description' => 'This is a generated description.']);
    }

    public function test_ai_generation_requires_name()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('ai.generate'), [
                'attributes' => ['Material' => 'Wood'],
            ]);

        $response->assertStatus(422);
    }
}
