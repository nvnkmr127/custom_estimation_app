<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAndSearchAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_admin_only()
    {
        $estimator = User::factory()->create(['role' => 'estimator']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($estimator)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    }

    public function test_search_scopes_estimates_to_owner_for_non_admins()
    {
        $owner = User::factory()->create(['role' => 'estimator']);
        $stranger = User::factory()->create(['role' => 'estimator']);
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
            'title' => 'ZzUniqueSearchableTitle',
        ]);

        // Stranger must not see the owner's estimate in search
        $strangerResults = $this->actingAs($stranger)
            ->getJson(route('search', ['q' => 'ZzUniqueSearchableTitle']))
            ->json();
        $this->assertEmpty(array_filter($strangerResults, fn ($r) => ($r['type'] ?? null) === 'Estimate'));

        // Owner sees their own
        $ownerResults = $this->actingAs($owner)
            ->getJson(route('search', ['q' => 'ZzUniqueSearchableTitle']))
            ->json();
        $this->assertNotEmpty(array_filter($ownerResults, fn ($r) => ($r['type'] ?? null) === 'Estimate'));

        // Admin sees everything
        $adminResults = $this->actingAs($admin)
            ->getJson(route('search', ['q' => 'ZzUniqueSearchableTitle']))
            ->json();
        $this->assertNotEmpty(array_filter($adminResults, fn ($r) => ($r['type'] ?? null) === 'Estimate'));
    }
}
