<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Estimate;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_loads_with_results()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-12345',
            'title' => 'Test Estimate Project'
        ]);

        $response = $this->actingAs($user)->get('/search?q=Test');

        $response->assertStatus(200);
        $response->assertViewIs('search.index');
        $response->assertSee('Test Estimate Project');
    }

    public function test_search_json_response()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        Estimate::factory()->create([
            'estimate_number' => 'EST-12345',
            'title' => 'Test Estimate JSON'
        ]);

        $response = $this->actingAs($user)->getJson('/search?q=Test');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['type', 'title', 'url', 'icon']
        ]);
    }
}
