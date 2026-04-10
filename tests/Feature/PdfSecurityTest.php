<?php

namespace Tests\Feature;

use App\Models\PdfTemplate;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_admin_can_update_unlocked_template()
    {
        RolePermission::firstOrCreate(['role' => 'estimator_admin', 'permission' => 'manage_pdf_templates']);
        \App\Services\PermissionService::clearCache('estimator_admin');

        $user = User::factory()->create(['role' => 'estimator_admin']);
        $template = PdfTemplate::factory()->create(['is_locked' => false]);

        $response = $this->actingAs($user)
            ->put(route('pdf-templates.update', $template), [
                'name' => 'Updated Name',
                'html_content' => '<div>{LOOP_ITEMS}</div><div>{grand_total}</div>',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#333333',
                'font_family' => 'Helvetica',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pdf_templates', ['id' => $template->id, 'name' => 'Updated Name']);
    }

    public function test_estimator_admin_cannot_update_locked_template()
    {
        RolePermission::firstOrCreate(['role' => 'estimator_admin', 'permission' => 'manage_pdf_templates']);
        \App\Services\PermissionService::clearCache('estimator_admin');

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
        RolePermission::firstOrCreate(['role' => 'super_admin', 'permission' => 'manage_pdf_templates']);
        \App\Services\PermissionService::clearCache('super_admin');

        $user = User::factory()->create(['role' => 'super_admin']);
        $template = PdfTemplate::factory()->create(['is_locked' => true]);

        $response = $this->actingAs($user)
            ->put(route('pdf-templates.update', $template), [
                'name' => 'Admin Update',
                'html_content' => '<div>{LOOP_ITEMS}</div><div>{grand_total}</div>',
                'paper_size' => $template->paper_size,
                'orientation' => $template->orientation,
                'primary_color' => $template->primary_color,
                'font_family' => $template->font_family,
                'is_locked' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pdf_templates', ['id' => $template->id, 'name' => 'Admin Update']);
    }

    public function test_update_creates_version_snapshot()
    {
        RolePermission::firstOrCreate(['role' => 'super_admin', 'permission' => 'manage_pdf_templates']);
        \App\Services\PermissionService::clearCache('super_admin');

        $user = User::factory()->create(['role' => 'super_admin']);
        $template = PdfTemplate::factory()->create([
            'html_content' => '<h1>Old Version</h1>',
        ]);

        $this->actingAs($user)
            ->put(route('pdf-templates.update', $template), [
                'name' => 'New Version Name',
                'html_content' => '<div>{LOOP_ITEMS}</div><div>{grand_total}</div>',
                'paper_size' => $template->paper_size,
                'orientation' => $template->orientation,
                'primary_color' => $template->primary_color,
                'font_family' => $template->font_family,
            ]);

        // Check if version was created with OLD content
        $this->assertDatabaseHas('pdf_template_versions', [
            'pdf_template_id' => $template->id,
            'version' => 1,
            'html_content' => '<h1>Old Version</h1>',
        ]);
    }
}
