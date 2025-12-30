<?php

namespace Tests\Feature;

use App\Models\PdfTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfStylingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_template_with_styling()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->post(route('pdf-templates.store'), [
            'name' => 'Styled Template',
            'html_content' => '<h1>Test</h1>',
            'css_content' => 'h1 { color: red; }',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'font_family' => 'Times',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('pdf-templates.index'));

        $this->assertDatabaseHas('pdf_templates', [
            'name' => 'Styled Template',
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00', // Note: Input name uses underscore, DB uses underscore.
            'font_family' => 'Times',
        ]);
    }

    public function test_can_update_template_styling()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $template = PdfTemplate::factory()->create([
            'primary_color' => '#000000',
        ]);

        $response = $this->actingAs($user)->put(route('pdf-templates.update', $template), [
            'name' => $template->name,
            'primary_color' => '#FFFFFF',
            'font_family' => 'Courier',
        ]);

        $response->assertRedirect(route('pdf-templates.index'));

        $this->assertDatabaseHas('pdf_templates', [
            'id' => $template->id,
            'primary_color' => '#FFFFFF',
            'font_family' => 'Courier',
        ]);
    }
}
