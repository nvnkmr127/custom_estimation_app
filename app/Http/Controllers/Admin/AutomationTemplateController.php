<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationTemplate;
use App\Services\Automation\TemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutomationTemplateController extends Controller
{
    protected $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * List all available templates (system + user's).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $templates = $this->templateService->getUserTemplates($user)
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'category' => $template->category,
                    'is_system' => $template->is_system,
                    'created_at' => $template->created_at->format('M d, Y'),
                ];
            });

        return response()->json(['templates' => $templates]);
    }

    /**
     * Save an existing automation as a template.
     */
    public function saveAsTemplate(Request $request, Automation $automation)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $template = $this->templateService->createFromAutomation(
            $automation,
            $request->name,
            $request->description,
            $request->category,
            $request->user()
        );

        return response()->json(['message' => 'Template created successfully', 'template' => $template]);
    }

    /**
     * Install a template (create new automation).
     */
    public function install(Request $request, AutomationTemplate $template)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        $overrides = [];
        if ($request->filled('name')) {
            $overrides['name'] = $request->name;
        }

        $automation = $this->templateService->install($template, $request->user(), $overrides);

        return response()->json([
            'message' => 'Automation created from template',
            'automation_id' => $automation->id,
            'redirect_url' => route('automation.index') . '?edit=' . $automation->id // Helper for frontend
        ]);
    }

    /**
     * Delete a template (user defined only).
     */
    public function destroy(AutomationTemplate $template)
    {
        if ($template->is_system) {
            return response()->json(['message' => 'Cannot delete system templates'], 403);
        }

        $template->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }
    /**
     * Export a template as a JSON file.
     */
    public function export(AutomationTemplate $template)
    {
        // Check access if needed (system templates are public, user templates owned)
        if (!$template->is_system && $template->created_by !== auth()->id()) {
            abort(403);
        }

        $filename = \Str::slug($template->name) . '-template.json';

        $data = [
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'structure' => $template->structure,
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Import a template from a JSON file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimetypes:application/json,text/plain|max:2048',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['structure'])) {
            return response()->json(['message' => 'Invalid template file format.'], 422);
        }

        $template = \App\Models\AutomationTemplate::create([
            'name' => ($data['name'] ?? 'Imported Template') . ' (Imported)',
            'description' => $data['description'] ?? 'Imported from file.',
            'category' => $data['category'] ?? 'Imported',
            'is_system' => false,
            'created_by' => auth()->id(),
            'structure' => $data['structure'],
        ]);

        return response()->json([
            'message' => 'Template imported successfully.',
            'template' => $template
        ]);
    }
}
