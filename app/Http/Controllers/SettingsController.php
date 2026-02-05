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
        $settings = Setting::all()->pluck('value', 'key');
        $timezones = \DateTimeZone::listIdentifiers();

        return view('settings.edit', compact('settings', 'timezones'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // General
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|max:2048',
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

            // Perfex CRM
            'perfex_api_url' => 'nullable|url|max:255',
            'perfex_api_token' => 'nullable|string|max:255',

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
            'perfex_api_url',
            'perfex_api_token',
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
            'portal_company_gallery_title',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        // Handle Logo Upload
        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('settings', 'public');
            $publicPath = Storage::disk('public')->url($path);

            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $publicPath]
            );
        }

        // Handle Video Upload
        if ($request->hasFile('portal_company_video')) {
            $path = $request->file('portal_company_video')->store('showcase', 'public');
            $publicPath = Storage::disk('public')->url($path);

            Setting::updateOrCreate(
                ['key' => 'portal_company_video'],
                ['value' => $publicPath]
            );
        }

        // Handle Video Thumbnail
        if ($request->hasFile('portal_company_video_thumbnail')) {
            $path = $request->file('portal_company_video_thumbnail')->store('showcase', 'public');
            $publicPath = Storage::disk('public')->url($path);

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
                $images[] = Storage::disk('public')->url($path);
            }
            Setting::updateOrCreate(
                ['key' => 'portal_company_showcase_images'],
                ['value' => json_encode($images)]
            );
        }

        return redirect()->route('settings.edit')->with('success', 'Settings updated successfully.');
    }
    public function testPerfex(Request $request, \App\Services\PerfexApiService $api)
    {
        try {
            // Attempt 1: Fetch Lead (Skip Mock)
            $response = $api->getLeads(1, true);

            // Handle API Error Response - Try Fallback if 500/Timeout
            if (isset($response['error']) || (isset($response['status']) && $response['status'] === false)) {
                Log::warning("Test Perfex: Main leads list failed (500/Timeout), searching for 'naveen' to verify connection and fetch sample data...");

                // Step 2: Try Search (Specific term) - Searching for 'naveen' as requested
                $searchResponse = $api->searchLeads('naveen');

                // If the message contains "No data" or "status":false but NO "error" key (except for 404), it's a success
                $responseString = json_encode($searchResponse);
                $isAuthOk = str_contains($responseString, 'No data') || !isset($searchResponse['error']);

                if ($isAuthOk) {
                    return response()->json([
                        'success' => true,
                        'message' => 'API Connected Successfully! We verified your URL and Token using the Search endpoint (since the main Leads list is currently timing out on your server).',
                        'raw_sample' => $searchResponse
                    ]);
                }

                // Step 3: Try Single Lead Fetch (ID 1) as final fallback
                $singleLeadResponse = $api->getLead(1);
                if (!isset($singleLeadResponse['error'])) {
                    return response()->json([
                        'success' => true,
                        'message' => 'API Connected Successfully! Verified via Single Lead Fetch.',
                        'raw_sample' => $singleLeadResponse
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Connection Failed: All attempts (List, Search, Single) failed. Please check your Token and URL. Error: ' . ($searchResponse['message'] ?? $searchResponse['error'] ?? 'Unknown Auth Error')
                ]);
            }

            // Getting the first item if List actually worked
            $sample = $response[0] ?? $response['data'][0] ?? null;

            // Attempt 2: If no leads, try fetching Customers (maybe they assume they are leads)
            if (!$sample) {
                // We don't have a getCustomers method exposed, let's use searchLeads or similar, 
                // OR assuming if no leads, we just return message.
                // Actually, let's just return the raw response if it's empty so they see it's empty.
                return response()->json([
                    'success' => true,
                    'message' => 'Connected successfully, but no Leads found (Array is empty).',
                    'raw_sample' => $response
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data fetched successfully (REAL DATA)',
                'raw_sample' => $sample
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }

    public function editPerfexMapping()
    {
        // Get existing mapping or default
        $rawMapping = Setting::where('key', 'perfex_field_mapping')->value('value');
        $mapping = $rawMapping ? json_decode($rawMapping, true) : [
            ['perfex_field' => 'phonenumber', 'local_field' => 'phone'],
            ['perfex_field' => 'address', 'local_field' => 'address'],
            ['perfex_field' => 'Location', 'local_field' => 'city'],
            ['perfex_field' => 'Property Name', 'local_field' => 'property_name'],
            ['perfex_field' => 'Type of property', 'local_field' => 'property_type'],
            ['perfex_field' => 'Alternative Number', 'local_field' => 'secondary_phone'],
            ['perfex_field' => 'Alternative Email', 'local_field' => 'secondary_email'],
        ];

        // Define available Client columns (Reduced list per user request)
        $clientColumns = [
            'name' => 'Name',
            'company' => 'Company',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'city' => 'City',
            // 'state' => 'State', // Removed as requested
            // 'zip' => 'Zip Code',    // Removed as requested
            // 'country' => 'Country', // Removed as requested
            'property_name' => 'Property Name',
            'property_address' => 'Property Address',
            'property_type' => 'Property Type',
            'property_notes' => 'Property Notes', // User didn't strictly ask to remove this, but based on "clients create" view removal, maybe? I'll keep it as it's useful for mapping 'notes'.
            'secondary_email' => 'Alternative Email',
            'secondary_phone' => 'Alternative Phone',
        ];

        return view('settings.perfex_mapping', compact('mapping', 'clientColumns'));
    }

    public function updatePerfexMapping(Request $request)
    {
        $mappings = $request->input('mappings', []);

        // Filter out empty rows
        $cleanMappings = [];
        foreach ($mappings as $map) {
            if (!empty($map['perfex_field']) && !empty($map['local_field'])) {
                $cleanMappings[] = [
                    'perfex_field' => trim($map['perfex_field']),
                    'local_field' => $map['local_field'],
                    'strategy' => $map['strategy'] ?? 'direct' // direct, append
                ];
            }
        }

        Setting::updateOrCreate(
            ['key' => 'perfex_field_mapping'],
            ['value' => json_encode($cleanMappings)]
        );

        return redirect()->route('settings.perfex.mapping')->with('success', 'Perfex mapping updated successfully.');
    }
}
