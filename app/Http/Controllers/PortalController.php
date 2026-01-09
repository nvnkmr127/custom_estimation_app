<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    protected $perfexApi;

    protected $analytics;

    public function __construct(\App\Services\PerfexApiService $perfexApi, \App\Services\AnalyticsService $analytics)
    {
        $this->perfexApi = $perfexApi;
        $this->analytics = $analytics;
    }

    /**
     * Display the specified estimate to the client.
     */
    public function show(Request $request, Estimate $estimate)
    {
        // Ensure the signed URL is valid
        if (!$request->hasValidSignature()) {
            abort(403, 'This link has expired or is invalid.');
        }

        // Track view
        $this->analytics->logAccess($estimate, 'view');
        $estimate->increment('view_count');
        $estimate->update(['last_viewed_at' => now()]);

        \App\Models\ActivityLog::log('proposal_viewed', $estimate, "Proposal for Estimate #{$estimate->estimate_number} was viewed by the client.");

        // Load items for the view
        $estimate->load(['sections.items', 'comments.user']); // Load comments with user (if any internal replies are visible, though currently filtering for client)

        // Render PDF Template HTML
        $template = $estimate->pdfTemplate ?? \App\Models\PdfTemplate::where('is_active', true)->where('is_default', true)->first() ?? \App\Models\PdfTemplate::first();

        $htmlContent = '';
        if ($template) {
            $service = new \App\Services\PdfRenderingService;
            $htmlContent = $service->render($template, $estimate);
        }

        return view('portal.estimates.show', compact('estimate', 'htmlContent'));
    }

    /**
     * Mark the estimate as accepted.
     */
    public function accept(Request $request, Estimate $estimate)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $request->validate([
            'signature' => 'required|string',
        ]);

        if ($estimate->status === 'accepted') {
            return redirect()->back()->with('info', 'This estimate has already been accepted.');
        }

        // Capture Location (Simple Lookup)
        $location = null;
        try {
            $ip = $request->ip();
            if ($ip !== '127.0.0.1' && $ip !== '::1') {
                $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,country,regionName", false, stream_context_create(['http' => ['timeout' => 2]]));
                if ($json) {
                    $data = json_decode($json, true);
                    if ($data && ($data['status'] ?? '') === 'success') {
                        $location = ($data['city'] ?? '') . ', ' . ($data['regionName'] ?? '') . ', ' . ($data['country'] ?? '');
                        $location = trim($location, ', ');
                    }
                }
            } else {
                $location = 'Localhost';
            }
        } catch (\Exception $e) {
            // Ignore location errors
        }

        $estimate->update([
            'status' => 'accepted',
            'signature' => $request->signature,
            'signed_at' => now(),
            'signer_ip' => $request->ip(),
            'signer_agent' => $request->userAgent(),
            'signer_location' => $location,
        ]);

        // Auto-Sync to Perfex
        $this->perfexApi->syncEstimate($estimate);

        // Notify Admins
        $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin'])->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\EstimateStatusUpdated($estimate, 'accepted'));

        return redirect()->back()->with('success', 'Thank you! You have successfully signed and accepted the estimate. It has also been synced to our CRM.');
    }

    /**
     * Mark the estimate as declined.
     */
    public function decline(Request $request, Estimate $estimate)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $request->validate([
            'client_notes' => 'required|string|max:1000',
        ]);

        $estimate->update([
            'status' => 'declined',
            'client_notes' => $request->client_notes,
        ]);

        // Notify Admins
        $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin'])->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\EstimateStatusUpdated($estimate, 'declined'));

        return redirect()->back()->with('success', 'You have declined the estimate. Thank you for your feedback.');
    }
    /**
     * Store a comment from the client.
     */
    public function comment(Request $request, Estimate $estimate)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
        ]);

        $comment = $estimate->comments()->create([
            'comment' => $request->comment,
            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
            'type' => 'client',
            'is_read' => false,
            'commentable_type' => \App\Models\Estimate::class,
            'commentable_id' => $estimate->id,
        ]);

        // Notify Creator and Followers
        $followers = $estimate->followers;
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate));
        }

        return back()->with('success', 'Thank you! Your comment has been sent to the team.');
    }
}
