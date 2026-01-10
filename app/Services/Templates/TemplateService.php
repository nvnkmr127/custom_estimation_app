<?php

namespace App\Services\Templates;

use App\Models\EmailTemplate;
use App\Services\Brands\BrandService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class TemplateService
{
    protected $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    /**
     * Render a blade view with data.
     * Looks for DB template first using view name as code.
     *
     * @param string $view e.g. 'emails.welcome' or 'welcome_email'
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        // 1. Inject Branding Variables
        $brandData = $this->brandService->getBrandVariables();
        $data = array_merge($brandData, $data);

        // 2. Try to find Database Template
        // We match "emails.welcome" -> code "emails.welcome" OR just "welcome"?
        // Let's assume view name is the code for now.
        $template = EmailTemplate::where('code', $view)->first();

        if ($template) {
            // Render from DB Content
            // We need to support body + potentially subject wrapping (not handled here, body only)
            // If using DB template, we usually expect full HTML or body content.
            // If it's body only, we need to wrap it in layout?
            // For Phase 2, let's assume DB content IS the body html, but we still need to process variables.

            // Note: If DB content relies on Layout, we might need to wrap it ourselves 
            // or assume it includes layout structure. 
            // Simplest Approach: Render DB content as raw string replaced with variables.
            return $this->renderString($template->body_html, $data);
        }

        // 3. Fallback to File View
        return View::make($view, $data)->render();
    }

    /**
     * Render a raw string with blade-like variable replacement.
     * Use Blade::render for full power (requires Laravel 9+)
     *
     * @param string $content
     * @param array $data
     * @return string
     */
    public function renderString(string $content, array $data = []): string
    {
        // Use Laravel's Blade::render if available (Laravel 9+)
        // Assuming Laravel 10/11 environment based on recent dates.
        try {
            return Blade::render($content, $data);
        } catch (\Exception $e) {
            // Fallback to simple replacement if Blade fails or not supported (or syntax error in user template)
            // \Illuminate\Support\Facades\Log::warning("Blade render failed for template: " . $e->getMessage());

            foreach ($data as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $content = str_replace('{{ ' . $key . ' }}', $value, $content);
                    $content = str_replace('{{' . $key . '}}', $value, $content);
                    $content = str_replace('{{ $' . $key . ' }}', $value, $content); // Blade style
                }
            }
            return $content;
        }
    }
}
