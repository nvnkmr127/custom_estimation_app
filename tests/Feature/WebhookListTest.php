<?php

namespace Tests\Feature;

use App\Livewire\Admin\Webhooks\Index;
use App\Models\WebhookConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebhookListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_the_component()
    {
        $admin = User::factory()->create(); // Assuming we don't need roles for unit test of component logic, or we bypass middleware for component test if not asserting full route access
        // For component test, we can just test the component.

        WebhookConfig::factory()->create(['name' => 'Searchable Webhook']);

        Livewire::test(Index::class)
            ->assertOk()
            ->assertSee('Searchable Webhook');
    }

    /** @test */
    public function it_filters_by_search_string()
    {
        WebhookConfig::factory()->create(['name' => 'Alpha']);
        WebhookConfig::factory()->create(['name' => 'Beta']);

        Livewire::test(Index::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha')
            ->assertDontSee('Beta');
    }

    /** @test */
    public function it_toggles_status()
    {
        $webhook = WebhookConfig::factory()->create(['status' => 'active']);

        Livewire::test(Index::class)
            ->call('toggleStatus', $webhook->id);

        $this->assertEquals('inactive', $webhook->fresh()->status);

        Livewire::test(Index::class)
            ->call('toggleStatus', $webhook->id);

        $this->assertEquals('active', $webhook->fresh()->status);
    }
}
