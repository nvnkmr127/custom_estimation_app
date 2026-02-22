<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\PdfTemplate;
use App\Services\PdfRenderingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_if_logic_renders_correctly()
    {
        $estimate = new Estimate([
            'estimate_number' => 'EST-123',
            'client_note' => 'Has Note',
            'terms' => null, // Null value
            'total_tax' => 0.00, // Zero value
        ]);

        $template = new PdfTemplate([
            'html_content' => '
                {IF_estimate_number}SHOW_NUM{END_IF}
                {IF_client_note}SHOW_NOTE{END_IF}
                {IF_terms}SHOW_TERMS{END_IF}
                {IF_tax_total}SHOW_TAX{END_IF}
            ',
            'primary_color' => '#000',
            'secondary_color' => '#000',
            'font_family' => 'Helvetica',
        ]);

        $service = new PdfRenderingService;
        $output = $service->render($template, $estimate);

        $this->assertStringContainsString('SHOW_NUM', $output);
        $this->assertStringContainsString('SHOW_NOTE', $output);
        $this->assertStringNotContainsString('SHOW_TERMS', $output);
        $this->assertStringNotContainsString('SHOW_TAX', $output);
    }

    public function test_generic_if_not_logic_renders_correctly()
    {
        $estimate = new Estimate([
            'estimate_number' => 'EST-123',
            'terms' => null,
        ]);

        // {IF_NOT_terms} should show because terms is null
        // {IF_NOT_estimate_number} should NOT show because it exists
        $template = new PdfTemplate([
            'html_content' => '
                {IF_NOT_terms}NO_TERMS{END_IF}
                {IF_NOT_estimate_number}NO_NUM{END_IF}
            ',
            'primary_color' => '#000',
            'secondary_color' => '#000',
            'font_family' => 'Helvetica',
        ]);

        $service = new PdfRenderingService;
        $output = $service->render($template, $estimate);

        $this->assertStringContainsString('NO_TERMS', $output);
        $this->assertStringNotContainsString('NO_NUM', $output);
    }

    public function test_inline_css_variables_are_replaced_correctly()
    {
        $estimate = new Estimate([
            'estimate_number' => 'EST-124',
        ]);

        $template = new PdfTemplate([
            'html_content' => '<div style="color: var(--primary-color); border: 1px solid var(--secondary-color); font-family: var(--font-body);">Test</div>',
            'primary_color' => '#123456',
            'secondary_color' => '#654321',
            'font_family' => 'Arial',
        ]);

        $service = new PdfRenderingService;
        $output = $service->render($template, $estimate);

        $this->assertStringContainsString('color: #123456', $output);
        $this->assertStringContainsString('border: 1px solid #654321', $output);
        $this->assertStringContainsString("font-family: 'Arial'", $output);
        // Verify the var() syntax was removed
        $this->assertStringNotContainsString('var(--primary-color)', $output);
        $this->assertStringNotContainsString('var(--secondary-color)', $output);
        $this->assertStringNotContainsString('var(--font-body)', $output);
    }
}
