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
        $admin = User::factory()->create(['role' => 'super_admin']);

        WebhookConfig::factory()->create(['name' => 'Searchable Webhook']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertOk()
            ->assertSee('Searchable Webhook');
    }

    /** @test */
    public function it_filters_by_search_string()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        WebhookConfig::factory()->create(['name' => 'Alpha']);
        WebhookConfig::factory()->create(['name' => 'Beta']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha')
            ->assertDontSee('Beta');
    }

    /** @test */
    public function it_toggles_status()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $webhook = WebhookConfig::factory()->create(['status' => 'active']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('toggleStatus', $webhook->id);

        $this->assertEquals('inactive', $webhook->fresh()->status);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('toggleStatus', $webhook->id);

        $this->assertEquals('active', $webhook->fresh()->status);
    }
}
