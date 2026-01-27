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
        $systemTemplates = PdfTemplate::where('type', 'system')->get();
        $variables = \App\Services\PdfRenderingService::getAvailableVariables();
        return view('pdf_templates.create', compact('systemTemplates', 'variables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'paper_size' => 'required|in:a4,letter',
            'orientation' => 'required|in:portrait,landscape',
            'primary_color' => 'required|string',
            'secondary_color' => 'nullable|string',
            'font_family' => 'required|string',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'watermark_text' => 'nullable|string',
            'watermark_opacity' => 'nullable|numeric|min:0|max:1',
            'is_password_protected' => 'boolean',
            'content_structure' => 'nullable|json',
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
            'content_structure' => isset($validated['content_structure']) ? json_decode($validated['content_structure'], true) : null,
        ]);

        return redirect()->route('pdf-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(PdfTemplate $pdfTemplate)
    {
        $this->authorize('update', $pdfTemplate);

        $pdfTemplate->load('versions.creator');
        $systemTemplates = PdfTemplate::where('type', 'system')->get();
        $variables = \App\Services\PdfRenderingService::getAvailableVariables();

        return view('pdf_templates.edit', compact('pdfTemplate', 'systemTemplates', 'variables'));
    }

    public function update(Request $request, PdfTemplate $pdfTemplate)
    {
        $this->authorize('update', $pdfTemplate);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'paper_size' => 'required|in:a4,letter',
            'orientation' => 'required|in:portrait,landscape',
            'primary_color' => 'required|string',
            'secondary_color' => 'nullable|string',
            'font_family' => 'required|string',
            'is_active' => 'sometimes|boolean',
            'is_locked' => 'sometimes|boolean',
            'watermark_text' => 'nullable|string|max:50',
            'watermark_opacity' => 'nullable|numeric|min:0|max:1',
            'is_password_protected' => 'sometimes|boolean',
            'content_structure' => 'nullable|json',
        ]);

        // Create Version Snapshot
        \App\Models\PdfTemplateVersion::create([
            'pdf_template_id' => $pdfTemplate->id,
            'version' => $pdfTemplate->versions()->count() + 1,
            'html_content' => $pdfTemplate->html_content,
            'css_content' => $pdfTemplate->css_content,
            'content_structure' => $pdfTemplate->content_structure,
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
            'content_structure' => isset($validated['content_structure']) ? json_decode($validated['content_structure'], true) : null,
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
            'content_structure' => $version->content_structure,
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

        // Set estimate type to room_based to show sections in preview
        $estimate->type = 'room_based';

        // Create Dummy Sections
        $section1 = new \App\Models\EstimateSection(['name' => 'Primary Bedroom', 'subtotal' => 1250.00]);
        $section2 = new \App\Models\EstimateSection(['name' => 'Gourmet Kitchen', 'subtotal' => 2800.00]);

        // Add dummy items for loops
        $item1 = new \App\Models\EstimateItem([
            'name' => 'Premium Wall Paint',
            'description' => 'Two-coat eggshell finish with primer',
            'quantity' => 1200,
            'unit_price' => 1.50,
            'total' => 1800.00,
            'unit_type' => 'sqft',
            'size' => 'Main Walls',
            'formula' => 'area',
            'length' => 60,
            'width' => 20
        ]);

        $product1 = new \App\Models\Product();
        $product1->setRelation('images', collect([
            (object) ['url' => 'https://images.unsplash.com/photo-1562184552-997c461abbe6?auto=format&fit=crop&w=100&q=80']
        ]));
        $item1->setRelation('product', $product1);

        $item2 = new \App\Models\EstimateItem([
            'name' => 'Custom Cabinetry',
            'description' => 'Solid oak with soft-close hinges',
            'quantity' => 1,
            'unit_price' => 2250.00,
            'total' => 2250.00,
            'unit_type' => 'ea',
            'formula' => 'fixed'
        ]);

        // Attach items to sections
        $section1->setRelation('items', collect([$item1]));
        $section2->setRelation('items', collect([$item2]));

        $estimate->setRelation('sections', collect([$section1, $section2]));
        $estimate->setRelation('items', collect([$item1, $item2]));

        $service = new PdfRenderingService;

        $renderedInfo = $service->render($tempTemplate, $estimate);

        return response($renderedInfo);
    }
}
