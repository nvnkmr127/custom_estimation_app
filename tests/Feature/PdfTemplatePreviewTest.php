<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PdfTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_endpoint_returns_html()
    {
        // We need an authenticated user if the route is protected (it probably is via 'auth' middleware group or check)
        // Checking routes/web.php: Route::post('/pdf-templates/preview'...) is under 'role:super_admin,estimator_admin' middleware group?
        // Let's assume we need admin permissions.

        // Create a verified user to bypass 'verified' middleware
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create role if it doesn't exist (it won't in RefreshDatabase)
        // Check if Role class exists
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $role = \Spatie\Permission\Models\Role::create(['name' => 'super_admin']);
            $user->assignRole($role);
        }

        // If Spatie permissions are used:
        // $user->assignRole('super_admin'); 
        // But for now, let's just try acting as user. If 403, we'll fix.

        $this->withoutMiddleware();

        $response = $this->actingAs($user)->postJson(route('pdf-templates.preview'), [
            'html_content' => '<h1>Test Estimate</h1> {LOOP_ITEMS} <p>{item_name}</p> {END_LOOP} {grand_total}',
            'css_content' => 'body { color: red; }',
            'preview_state' => [
                'roomBased' => false,
                'hasTax' => true,
                'hasDiscount' => true
            ]
        ]);

        // If it fails with 500, we'll see the error in the JSON response now!
        if ($response->status() === 500) {
            dump($response->json());
        }

        $response->assertStatus(200);
        $response->assertSee('Test Estimate');
        // Check if items loop was processed (dummy item names should be present)
        $response->assertSee('Premium Wall Paint');
    }

    public function test_preview_handles_css_with_page_break()
    {
        // This used to cause a 500 error because of HTML Purifier config
        // Now it should be stripped or ignored, but definitely return 200 OK

        // Create verified user
        $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
            $user->assignRole($role);
        }

        $this->withoutMiddleware();

        $response = $this->actingAs($user)->postJson(route('pdf-templates.preview'), [
            'html_content' => '<div style="page-break-after: always;">Wait for it...</div>',
            'css_content' => '',
            'preview_state' => ['roomBased' => false]
        ]);

        $response->assertStatus(200);
        $response->assertSee('Wait for it...');
    }
}
