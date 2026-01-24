<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
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
            $path = $request->file('app_logo')->store('public/settings');
            $publicPath = Storage::url($path);

            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $publicPath]
            );
        }

        return redirect()->route('settings.edit')->with('success', 'Settings updated successfully.');
    }
}
