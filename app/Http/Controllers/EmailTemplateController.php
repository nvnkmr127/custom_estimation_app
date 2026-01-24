<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        return view('email_templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('email_templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:email_templates,code',
            'name' => 'required|string',
            'subject' => 'required|string',
            'body_html' => 'required|string',
            'description' => 'nullable|string',
            'variables' => 'nullable|array',
        ]);

        EmailTemplate::create($validated);

        return redirect()->route('email-templates.index')->with('success', 'Template created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailTemplate $emailTemplate)
    {
        return view('email_templates.show', compact('emailTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        return view('email_templates.edit', compact('emailTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:email_templates,code,' . $emailTemplate->id,
            'name' => 'required|string',
            'subject' => 'required|string',
            'body_html' => 'required|string',
            'description' => 'nullable|string',
            'variables' => 'nullable|array',
        ]);

        $emailTemplate->update($validated);

        return redirect()->route('email-templates.index')->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        return redirect()->route('email-templates.index')->with('success', 'Template deleted successfully.');
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'body_html' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        $templateService = app(\App\Services\Templates\TemplateService::class);
        $content = $templateService->renderString($validated['body_html'], $validated['variables'] ?? []);

        return response()->json(['html' => $content]);
    }
}
