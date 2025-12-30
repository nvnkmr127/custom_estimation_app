<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\PdfTemplate;
use App\Services\PdfRenderingService;
use Illuminate\Http\Request;

class PdfTemplateController extends Controller
{
    public function index()
    {
        $templates = PdfTemplate::latest()->paginate(10);

        return view('pdf_templates.index', compact('templates'));
    }

    public function create()
    {
        // Load a default starter template
        $defaultHtml = file_get_contents(resource_path('views/estimates/print_modern.blade.php'));
        // Strip blade directives for safety/simplicity in this MVP editor version
        // Ideally we provide a clean HTML starting point
        $starterHtml = '<html><body><h1>Estimate #{estimate_number}</h1>{LOOP_ITEMS}<div>{item_name} - {item_price}</div>{END_LOOP}</body></html>';

        return view('pdf_templates.create', compact('starterHtml'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'nullable|string',
            'css_content' => 'nullable|string',
            'paper_size' => 'nullable|in:a4,letter',
            'orientation' => 'nullable|in:portrait,landscape',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'font_family' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_locked' => 'sometimes|boolean',
            'watermark_text' => 'nullable|string|max:50',
            'watermark_opacity' => 'nullable|numeric|min:0|max:1',
            'is_password_protected' => 'sometimes|boolean',
        ]);

        $this->authorize('create', PdfTemplate::class);

        $template = PdfTemplate::create([
            'name' => $validated['name'],
            'html_content' => $validated['html_content'] ?? '',
            'css_content' => $validated['css_content'] ?? '',
            'paper_size' => $validated['paper_size'] ?? 'a4',
            'orientation' => $validated['orientation'] ?? 'portrait',
            'primary_color' => $validated['primary_color'] ?? '#333333',
            'secondary_color' => $validated['secondary_color'] ?? '#555555',
            'font_family' => $validated['font_family'] ?? 'Helvetica',
            'is_active' => $request->has('is_active'),
            'is_default' => false,
            'is_locked' => $request->has('is_locked'),
            'watermark_text' => $validated['watermark_text'] ?? null,
            'watermark_opacity' => $validated['watermark_opacity'] ?? 0.1,
            'is_password_protected' => $request->has('is_password_protected'),
        ]);

        return redirect()->route('pdf-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(PdfTemplate $pdfTemplate)
    {
        $this->authorize('update', $pdfTemplate);

        return view('pdf_templates.edit', compact('pdfTemplate'));
    }

    public function update(Request $request, PdfTemplate $pdfTemplate)
    {
        $this->authorize('update', $pdfTemplate);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'nullable|string',
            'css_content' => 'nullable|string',
            'paper_size' => 'nullable|in:a4,letter',
            'orientation' => 'nullable|in:portrait,landscape',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'font_family' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_locked' => 'sometimes|boolean',
            'watermark_text' => 'nullable|string|max:50',
            'watermark_opacity' => 'nullable|numeric|min:0|max:1',
            'is_password_protected' => 'sometimes|boolean',
        ]);

        // Create Version Snapshot
        \App\Models\PdfTemplateVersion::create([
            'pdf_template_id' => $pdfTemplate->id,
            'version' => $pdfTemplate->versions()->count() + 1,
            'html_content' => $pdfTemplate->html_content,
            'css_content' => $pdfTemplate->css_content,
            'created_by' => auth()->id(),
        ]);

        $pdfTemplate->update([
            'name' => $validated['name'],
            'html_content' => $validated['html_content'] ?? '',
            'css_content' => $validated['css_content'] ?? '',
            'paper_size' => $validated['paper_size'] ?? 'a4',
            'orientation' => $validated['orientation'] ?? 'portrait',
            'primary_color' => $validated['primary_color'] ?? '#333333',
            'secondary_color' => $validated['secondary_color'] ?? '#555555',
            'font_family' => $validated['font_family'] ?? 'Helvetica',
            'is_active' => $request->has('is_active'),
            'is_locked' => $request->has('is_locked'),
            'watermark_text' => $validated['watermark_text'] ?? null,
            'watermark_opacity' => $validated['watermark_opacity'] ?? 0.1,
            'is_password_protected' => $request->has('is_password_protected'),
        ]);

        return redirect()->route('pdf-templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(PdfTemplate $pdfTemplate)
    {
        $this->authorize('delete', $pdfTemplate);
        $pdfTemplate->delete();

        return redirect()->route('pdf-templates.index')->with('success', 'Template deleted.');
    }

    public function restore(PdfTemplate $pdfTemplate, $versionId)
    {
        $this->authorize('update', $pdfTemplate);

        $version = \App\Models\PdfTemplateVersion::where('pdf_template_id', $pdfTemplate->id)->findOrFail($versionId);

        $pdfTemplate->update([
            'html_content' => $version->html_content,
            'css_content' => $version->css_content,
        ]);

        return back()->with('success', "Restored to version {$version->version}.");
    }

    public function preview(Request $request)
    {
        $html = $request->input('html_content');
        $css = $request->input('css_content');

        // Create a dummy estimate for preview
        $estimate = new Estimate([
            'estimate_number' => 'EST-PREVIEW',
            'estimate_date' => now(),
            'expiry_date' => now()->addDays(7),
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 1000,
            'total_tax' => 100,
            'grand_total' => 1100,
        ]);

        // Use the service to render
        // We create a temporary template object just for rendering
        $tempTemplate = new PdfTemplate([
            'html_content' => $html,
            'css_content' => $css,
            'watermark_text' => $request->input('watermark_text'),
            'watermark_opacity' => $request->input('watermark_opacity', 0.1),
            'primary_color' => $request->input('primary_color', '#333333'), // For watermark color
            'secondary_color' => $request->input('secondary_color', '#555555'),
            'font_family' => $request->input('font_family', 'Helvetica'),
        ]);

        $service = new PdfRenderingService;

        // Mock items needs logic in service or meaningful dummy data
        // For now, let's just let the variables fail gracefully or show empty

        $renderedInfo = $service->render($tempTemplate, $estimate);

        return response($renderedInfo);
    }
}
