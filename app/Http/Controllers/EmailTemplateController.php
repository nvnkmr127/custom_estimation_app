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
        $eventService = app(\App\Services\Events\EventMetadataService::class);
        $events = $eventService->getSupportedEvents();
        return view('email_templates.edit', compact('events')); // We reuse edit for create too
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:email_templates,code',
            'event_trigger' => 'required|string',
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
        $eventService = app(\App\Services\Events\EventMetadataService::class);
        $events = $eventService->getSupportedEvents();
        return view('email_templates.edit', compact('emailTemplate', 'events'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:email_templates,code,' . $emailTemplate->id,
            'event_trigger' => 'required|string',
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
            'subject' => 'nullable|string',
        ]);

        $templateService = app(\App\Services\Templates\TemplateService::class);
        
        // Mock some data for preview
        $mockData = [
            'estimate_number' => 'EST-2026-PREVIEW',
            'total_amount' => '₹ 5,20,000.00',
            'client_name' => 'John Doe (Sample)',
            'notifiable_name' => 'Valued Client',
            'view_url' => '#',
            'pixel_url' => '#',
            'expires_in' => '24 hours',
            'subject' => $validated['subject'] ?? 'Template Preview',
        ];

        // Wrap in Layout for preview if it doesn't have one
        $content = $validated['body_html'];
        if (!str_contains($content, '@extends') && !str_contains($content, '<html>')) {
            $content = "@extends('emails.layout')\n@section('content')\n" . $content . "\n@endsection";
        }

        try {
            $html = $templateService->renderString($content, $mockData);
            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['html' => "<div style='color:red;padding:20px;'><strong>Template Error:</strong><br>{$e->getMessage()}</div>"], 422);
        }
    }

    public function test(Request $request, EmailTemplate $emailTemplate)
    {
        $user = auth()->user();
        $emailDispatcher = app(\App\Services\Mail\EmailDispatcher::class);

        // Mock data for the test
        $mockData = [
            'estimate_number' => 'TEST-0001',
            'total_amount' => '₹ 1,00,000.00',
            'client_name' => $user->name,
            'notifiable_name' => $user->name,
            'view_url' => route('dashboard'),
            'pixel_url' => '#',
        ];

        $emailDispatcher->dispatch(
            $user->email, 
            "[TEST] " . $emailTemplate->subject, 
            $emailTemplate->code, 
            $mockData
        );

        return back()->with('success', "Test email sent to {$user->email}");
    }
}
