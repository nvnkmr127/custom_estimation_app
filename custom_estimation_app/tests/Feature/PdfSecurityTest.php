<?php

namespace Tests\Feature;

use App\Models\PdfTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_admin_can_update_unlocked_template()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $template = PdfTemplate::factory()->create(['is_locked' => false]);

        $response = $this->actingAs($user)
            ->put(route('pdf-templates.update', $template), [
                'name' => 'Updated Name',
                'html_content' => '<h1>New Content</h1>',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pdf_templates', ['id' => $template->id, 'name' => 'Updated Name']);
    }

    public function test_estimator_admin_cannot_update_locked_template()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $template = PdfTemplate::factory()->create(['is_locked' => true]);

        $response = $this->actingAs($user)
            ->put(route('pdf-templates.update', $template), [
                'name' => 'Try Update',
            ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_update_locked_template()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $template = PdfTemplate::factory()->create(['is_locked' => true]);

        $response = $this->actingAs($user)
            ->put(route('pdf-templates.update', $template), [
                'name' => 'Admin Update',
                'is_locked' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pdf_templates', ['id' => $template->id, 'name' => 'Admin Update']);
    }

    public function test_update_creates_version_snapshot()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $template = PdfTemplate::factory()->create([
            'html_content' => '<h1>Old Version</h1>',
        ]);

        $this->actingAs($user)
            ->put(route('pdf-templates.update', $template), [
                'name' => 'New Version Name',
                'html_content' => '<h1>New Version</h1>',
            ]);

        // Check if version was created with OLD content
        $this->assertDatabaseHas('pdf_template_versions', [
            'pdf_template_id' => $template->id,
            'version' => 1,
            'html_content' => '<h1>Old Version</h1>',
        ]);
    }
}
