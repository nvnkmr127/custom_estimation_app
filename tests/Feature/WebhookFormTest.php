<?php

namespace Tests\Feature;

use App\Livewire\Admin\Webhooks\Form;
use App\Models\WebhookConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class WebhookFormTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_webhook_config()
    {
        Livewire::test(Form::class)
            ->set('state.name', 'New Hook')
            ->set('state.url', 'https://example.com')
            ->set('state.events', ['estimate.created'])
            ->set('state.secret', 'secret_123')
            ->call('save')
            ->assertRedirect(route('admin.webhooks.index'));

        $this->assertDatabaseHas('webhooks', [
            'name' => 'New Hook',
            'url' => 'https://example.com'
        ]);
    }

    /** @test */
    public function it_updates_existing_webhook()
    {
        $webhook = WebhookConfig::factory()->create();

        Livewire::test(Form::class, ['webhook' => $webhook])
            ->set('state.name', 'Updated Name')
            ->call('save')
            ->assertRedirect(route('admin.webhooks.index'));

        $this->assertEquals('Updated Name', $webhook->fresh()->name);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(Form::class)
            ->call('save')
            ->assertHasErrors(['state.name', 'state.url', 'state.events']);
    }

    /** @test */
    public function it_tests_connection_successfully()
    {
        Http::fake([
            'example.com' => Http::response('OK', 200)
        ]);

        Livewire::test(Form::class)
            ->set('state.url', 'https://example.com')
            ->call('testConnection')
            ->assertSet('testStatus', 'success');
    }
}
