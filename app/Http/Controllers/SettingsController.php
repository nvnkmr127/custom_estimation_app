<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        $this->authorize('manage_settings');

        $settings = Setting::all()->pluck('value', 'key');
        
        // Security: Mask sensitive SMTP credentials before sending to view
        if (isset($settings['smtp_password'])) {
            $settings['smtp_password'] = '********';
        }

        $timezones = \DateTimeZone::listIdentifiers();

        return view('settings.edit', compact('settings', 'timezones'));
    }

    public function update(Request $request)
    {
        $this->authorize('manage_settings');

        $validated = $request->validate([
            // General
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|max:2048',
            'app_favicon' => 'nullable|image|max:1024',
            'app_timezone' => 'nullable|string|max:255',

            // Company Identity
            'company_legal_name' => 'nullable|string|max:255',
            'company_tax_id' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_website' => 'nullable|url|max:255',

            // Address
            'company_address_street' => 'nullable|string|max:255',
            'company_address_city' => 'nullable|string|max:100',
            'company_address_state' => 'nullable|string|max:100',
            'company_address_zip' => 'nullable|string|max:20',
            'company_address_country' => 'nullable|string|max:100',

            // Financial
            'currency_code' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:5',
            'fiscal_year_start' => 'nullable|date_format:m-d', // e.g., 01-01 or 04-01

            // Estimation Defaults
            'estimate_prefix' => 'nullable|string|max:10',
            'estimate_theme' => 'nullable|in:modern,classic,minimal',
            'pdf_theme' => 'nullable|in:modern,classic,minimal',
            'tax_1_name' => 'nullable|string|max:20',
            'tax_1_rate' => 'nullable|numeric|min:0|max:100',
            'tax_2_name' => 'nullable|string|max:20',
            'tax_2_rate' => 'nullable|numeric|min:0|max:100',
            'estimate_terms' => 'nullable|string',
            'estimate_client_note' => 'nullable|string',


            // SMTP Settings
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|numeric|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl,null',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',

            // Portal Showcase
            'portal_company_badge' => 'nullable|string|max:50',
            'portal_company_title' => 'nullable|string|max:255',
            'portal_company_intro' => 'nullable|string',
            'portal_company_video_url' => 'nullable|url|max:255',
            'portal_company_youtube_url' => 'nullable|url|max:255',
            'portal_company_video' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:20480',
            'portal_company_video_thumbnail' => 'nullable|image|max:4096',
            'portal_company_gallery_title' => 'nullable|string|max:255',
            'portal_company_showcase_images.*' => 'nullable|image|max:4096',
        ]);

        // Fields to save directly from input
        $keys = [
            'app_name',
            'app_timezone',
            'company_legal_name',
            'company_tax_id',
            'company_email',
            'company_phone',
            'company_website',
            'company_address_street',
            'company_address_city',
            'company_address_state',
            'company_address_zip',
            'company_address_country',
            'currency_code',
            'currency_symbol',
            'fiscal_year_start',
            'default_tax_rate', // Keeping for legacy/global ref if needed
            'estimate_prefix',
            'estimate_theme',
            'pdf_theme',
            'tax_1_name',
            'tax_1_rate',
            'tax_2_name',
            'tax_2_rate',
            'estimate_terms',
            'estimate_client_note',
            // SMTP
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
            'smtp_from_address',
            'smtp_from_name',
            'portal_company_badge',
            'portal_company_title',
            'portal_company_intro',
            'portal_company_video_url',
            'portal_company_youtube_url',
            'portal_company_gallery_title',
        ];

        foreach ($keys as $key) {
            $val = $request->input($key);

            // Security: Don't update sensitive fields if they are masked/placeholders
            if ($key === 'smtp_password' && ($val === '********' || empty($val))) {
                continue;
            }

            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $val]
                );
            }
        }

        // Handle Logo Upload
        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('settings', 'public');
            $publicPath = asset('storage/' . $path);

            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $publicPath]
            );
        }

        // Handle Favicon Upload
        if ($request->hasFile('app_favicon')) {
            $path = $request->file('app_favicon')->store('settings', 'public');
            $publicPath = asset('storage/' . $path);

            Setting::updateOrCreate(
                ['key' => 'app_favicon'],
                ['value' => $publicPath]
            );
        }

        // Handle Video Upload
        if ($request->hasFile('portal_company_video')) {
            $path = $request->file('portal_company_video')->store('showcase', 'public');
            $publicPath = asset('storage/' . $path);

            Setting::updateOrCreate(
                ['key' => 'portal_company_video'],
                ['value' => $publicPath]
            );
        }

        // Handle Video Thumbnail
        if ($request->hasFile('portal_company_video_thumbnail')) {
            $path = $request->file('portal_company_video_thumbnail')->store('showcase', 'public');
            $publicPath = asset('storage/' . $path);

            Setting::updateOrCreate(
                ['key' => 'portal_company_video_thumbnail'],
                ['value' => $publicPath]
            );
        }

        // Handle Portal Showcase Images (Multiple)
        if ($request->hasFile('portal_company_showcase_images')) {
            $images = [];
            foreach ($request->file('portal_company_showcase_images') as $file) {
                $path = $file->store('showcase', 'public');
                $images[] = asset('storage/' . $path);
            }
            Setting::updateOrCreate(
                ['key' => 'portal_company_showcase_images'],
                ['value' => json_encode($images)]
            );
        }

        return redirect()->route('settings.edit')->with('success', 'Settings updated successfully.');
    }

    public function testEmail(Request $request, \App\Services\Mail\Contracts\MailGatewayInterface $mailService)
    {
        $this->authorize('manage_settings');

        try {
            $request->validate([
                'email' => 'required|email|max:255',
                // Optional SMTP settings to test before saving
                'smtp_host' => 'nullable|string',
                'smtp_port' => 'nullable|numeric',
                'smtp_username' => 'nullable|string',
                'smtp_password' => 'nullable|string',
                'smtp_encryption' => 'nullable|string',
                'smtp_from_address' => 'nullable|email',
                'smtp_from_name' => 'nullable|string',
            ]);

            $recipient = $request->input('email');

            // If any SMTP settings are provided in request, override them temporarily for this send
            if ($request->filled('smtp_host')) {
                config([
                    'mail.mailers.smtp.host' => $request->input('smtp_host'),
                    'mail.mailers.smtp.port' => $request->input('smtp_port', 587),
                    'mail.mailers.smtp.encryption' => $request->input('smtp_encryption', 'tls'),
                    'mail.mailers.smtp.username' => $request->input('smtp_username'),
                    'mail.mailers.smtp.password' => $request->input('smtp_password'),
                    'mail.from.address' => $request->input('smtp_from_address', config('mail.from.address')),
                    'mail.from.name' => $request->input('smtp_from_name', config('mail.from.name')),
                ]);
                \Illuminate\Support\Facades\Mail::purge('smtp');
            }

            $appName = Setting::where('key', 'app_name')->value('value') ?? config('app.name');

            $subject = "Test Email from {$appName}";
            $body = "<h1>Test Email Successful!</h1><p>This is a test email sent from your <strong>{$appName}</strong> settings page to verify SMTP configuration.</p><p>Sent on: " . now()->format('Y-m-d H:i:s') . "</p>";

            $success = $mailService->send($recipient, $subject, $body);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => "Test email has been sent successfully to {$recipient}. Please check your inbox (and spam folder)."
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'SMTP Failed to send email. Please check your credentials and verify your SMTP server allows connections from this IP.'
            ]);

        } catch (\Exception $e) {
            Log::error('Settings Test Email Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection Error: ' . $e->getMessage()
            ]);
        }
    }


    public function deleteGalleryImage($index)
    {
        $this->authorize('manage_settings');

        $rawSetting = Setting::where('key', 'portal_company_showcase_images')->first();
        if (!$rawSetting) {
            return back()->with('error', 'No images found.');
        }

        $images = json_decode($rawSetting->value, true);
        if (!isset($images[$index])) {
            return back()->with('error', 'Image not found.');
        }

        $imagePath = $images[$index];

        // Remove from array
        unset($images[$index]);
        $images = array_values($images); // Re-index

        // Update DB
        if (empty($images)) {
            $rawSetting->delete();
        } else {
            $rawSetting->update(['value' => json_encode($images)]);
        }

        // Optionally delete from storage if it's a local file
        if (str_contains($imagePath, '/storage/')) {
            $storageUrl = asset('storage/');
            $storagePath = str_replace($storageUrl, '', $imagePath);
            Storage::disk('public')->delete($storagePath);
        }

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }

        return back()->with('success', 'Image deleted successfully.');
    }
}
