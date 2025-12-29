<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">ERP Settings</h1>
            <p class="mt-2 text-sm text-slate-500">Manage your organization profile, financial standards, and global
                preferences.</p>
        </div>
    </div>

    <div class="mt-8">
        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- Section 1: General & Identity -->
            <div class="grid grid-cols-1 gap-x-8 gap-y-8 md:grid-cols-3">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base font-semibold leading-7 text-slate-900">Organization Profile</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">The legal identity of your business. These details
                        appear on invoices and estimates.</p>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl md:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <!-- App Name -->
                            <div class="sm:col-span-4">
                                <label for="app_name"
                                    class="block text-sm font-medium leading-6 text-slate-900">Application Name</label>
                                <div class="mt-2">
                                    <input type="text" name="app_name" id="app_name"
                                        value="{{ old('app_name', $settings['app_name'] ?? config('app.name')) }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <!-- Logo -->
                            <div class="col-span-full">
                                <label for="app_logo"
                                    class="block text-sm font-medium leading-6 text-slate-900">Organization Logo</label>
                                <div class="mt-2 flex items-center gap-x-3">
                                    @if(isset($settings['app_logo']))
                                        <img src="{{ asset($settings['app_logo']) }}" alt="Current Logo"
                                            class="h-12 w-12 rounded-full object-cover bg-slate-50 ring-1 ring-slate-900/10">
                                    @else
                                        <div
                                            class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <input type="file" name="app_logo" id="app_logo"
                                        class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                </div>
                            </div>

                            <!-- Legal Name -->
                            <div class="sm:col-span-4">
                                <label for="company_legal_name"
                                    class="block text-sm font-medium leading-6 text-slate-900">Legal Name</label>
                                <div class="mt-2">
                                    <input type="text" name="company_legal_name" id="company_legal_name"
                                        value="{{ old('company_legal_name', $settings['company_legal_name'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <!-- Tax ID -->
                            <div class="sm:col-span-3">
                                <label for="company_tax_id"
                                    class="block text-sm font-medium leading-6 text-slate-900">Tax ID / VAT
                                    Number</label>
                                <div class="mt-2">
                                    <input type="text" name="company_tax_id" id="company_tax_id"
                                        value="{{ old('company_tax_id', $settings['company_tax_id'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Contact & Address -->
            <div class="grid grid-cols-1 gap-x-8 gap-y-8 md:grid-cols-3">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base font-semibold leading-7 text-slate-900">Contact Details</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Where clients can reach you and where you ship
                        from.</p>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl md:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="company_email"
                                    class="block text-sm font-medium leading-6 text-slate-900">Email Address</label>
                                <div class="mt-2">
                                    <input type="email" name="company_email" id="company_email"
                                        value="{{ old('company_email', $settings['company_email'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="company_phone"
                                    class="block text-sm font-medium leading-6 text-slate-900">Phone Number</label>
                                <div class="mt-2">
                                    <input type="text" name="company_phone" id="company_phone"
                                        value="{{ old('company_phone', $settings['company_phone'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="col-span-full">
                                <label for="company_address_street"
                                    class="block text-sm font-medium leading-6 text-slate-900">Street Address</label>
                                <div class="mt-2">
                                    <input type="text" name="company_address_street" id="company_address_street"
                                        value="{{ old('company_address_street', $settings['company_address_street'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-2 sm:col-start-1">
                                <label for="company_address_city"
                                    class="block text-sm font-medium leading-6 text-slate-900">City</label>
                                <div class="mt-2">
                                    <input type="text" name="company_address_city" id="company_address_city"
                                        value="{{ old('company_address_city', $settings['company_address_city'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="company_address_state"
                                    class="block text-sm font-medium leading-6 text-slate-900">State / Province</label>
                                <div class="mt-2">
                                    <input type="text" name="company_address_state" id="company_address_state"
                                        value="{{ old('company_address_state', $settings['company_address_state'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="company_address_zip"
                                    class="block text-sm font-medium leading-6 text-slate-900">ZIP / Postal Code</label>
                                <div class="mt-2">
                                    <input type="text" name="company_address_zip" id="company_address_zip"
                                        value="{{ old('company_address_zip', $settings['company_address_zip'] ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Financial Standards -->
            <div class="grid grid-cols-1 gap-x-8 gap-y-8 md:grid-cols-3">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base font-semibold leading-7 text-slate-900">Financial Standards</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Define your base currency and tax rules. Critical
                        for ERP reporting.</p>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl md:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="currency_code"
                                    class="block text-sm font-medium leading-6 text-slate-900">Currency Code</label>
                                <div class="mt-2">
                                    <select id="currency_code" name="currency_code"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        <option value="USD" {{ (old('currency_code', $settings['currency_code'] ?? 'USD') == 'USD') ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option value="INR" {{ (old('currency_code', $settings['currency_code'] ?? '') == 'INR') ? 'selected' : '' }}>INR - Indian Rupee</option>
                                        <option value="EUR" {{ (old('currency_code', $settings['currency_code'] ?? '') == 'EUR') ? 'selected' : '' }}>EUR - Euro</option>
                                        <option value="GBP" {{ (old('currency_code', $settings['currency_code'] ?? '') == 'GBP') ? 'selected' : '' }}>GBP - British Pound</option>
                                    </select>
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="currency_symbol"
                                    class="block text-sm font-medium leading-6 text-slate-900">Currency Symbol</label>
                                <div class="mt-2">
                                    <input type="text" name="currency_symbol" id="currency_symbol"
                                        value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '$') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="default_tax_rate"
                                    class="block text-sm font-medium leading-6 text-slate-900">Default Tax Rate
                                    (%)</label>
                                <div class="mt-2">
                                    <input type="number" step="0.01" name="default_tax_rate" id="default_tax_rate"
                                        value="{{ old('default_tax_rate', $settings['default_tax_rate'] ?? '0') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="fiscal_year_start"
                                    class="block text-sm font-medium leading-6 text-slate-900">Fiscal Year Start
                                    (MM-DD)</label>
                                <div class="mt-2">
                                    <input type="text" name="fiscal_year_start" id="fiscal_year_start"
                                        placeholder="01-01"
                                        value="{{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '01-01') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Used for annual reports.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Estimation Configuration -->
            <div class="grid grid-cols-1 gap-x-8 gap-y-8 md:grid-cols-3 pt-8 border-t border-slate-200">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base font-semibold leading-7 text-slate-900">Estimation Defaults</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Configure default values for new estimates to save
                        time and ensure consistency.</p>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl md:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                            <div class="sm:col-span-3">
                                <label for="estimate_prefix"
                                    class="block text-sm font-medium leading-6 text-slate-900">Estimate Number
                                    Prefix</label>
                                <div class="mt-2">
                                    <input type="text" name="estimate_prefix" id="estimate_prefix"
                                        value="{{ old('estimate_prefix', $settings['estimate_prefix'] ?? 'EST-') }}"
                                        placeholder="EST-"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="pdf_theme" class="block text-sm font-medium leading-6 text-slate-900">PDF
                                    Theme</label>
                                <div class="mt-2">
                                    <select id="pdf_theme" name="pdf_theme"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        <option value="modern" {{ (old('pdf_theme', $settings['pdf_theme'] ?? 'modern') == 'modern') ? 'selected' : '' }}>Modern (Clean Blue)</option>
                                        <option value="classic" {{ (old('pdf_theme', $settings['pdf_theme'] ?? 'modern') == 'classic') ? 'selected' : '' }}>Classic (Formal B&W)</option>
                                        <option value="minimal" {{ (old('pdf_theme', $settings['pdf_theme'] ?? 'modern') == 'minimal') ? 'selected' : '' }}>Minimal (Simple)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="sm:col-span-6 border-t border-gray-100 pt-4 mt-2">
                                <h3 class="text-sm font-medium text-slate-900 mb-4">Default Tax Rates</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700">Tax 1 Name (e.g.
                                            VAT)</label>
                                        <input type="text" name="tax_1_name"
                                            value="{{ old('tax_1_name', $settings['tax_1_name'] ?? '') }}"
                                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700">Tax 1 Rate (%)</label>
                                        <input type="number" step="0.01" name="tax_1_rate"
                                            value="{{ old('tax_1_rate', $settings['tax_1_rate'] ?? '0') }}"
                                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700">Tax 2 Name (e.g. Service
                                            Tax)</label>
                                        <input type="text" name="tax_2_name"
                                            value="{{ old('tax_2_name', $settings['tax_2_name'] ?? '') }}"
                                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700">Tax 2 Rate (%)</label>
                                        <input type="number" step="0.01" name="tax_2_rate"
                                            value="{{ old('tax_2_rate', $settings['tax_2_rate'] ?? '0') }}"
                                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-full">
                                <label for="estimate_client_note"
                                    class="block text-sm font-medium leading-6 text-slate-900">Default Client
                                    Note</label>
                                <div class="mt-2">
                                    <textarea id="estimate_client_note" name="estimate_client_note" rows="2"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('estimate_client_note', $settings['estimate_client_note'] ?? '') }}</textarea>
                                </div>
                            </div>

                            <div class="col-span-full">
                                <label for="estimate_terms"
                                    class="block text-sm font-medium leading-6 text-slate-900">Default Terms &
                                    Conditions</label>
                                <div class="mt-2">
                                    <textarea id="estimate_terms" name="estimate_terms" rows="4"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('estimate_terms', $settings['estimate_terms'] ?? '') }}</textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- Section 5: Integrations -->
                    <div class="grid grid-cols-1 gap-x-8 gap-y-8 md:grid-cols-3 pt-8 border-t border-slate-200">
                        <div class="px-4 sm:px-0">
                            <h2 class="text-base font-semibold leading-7 text-slate-900">Integrations</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Connect your CRM and external tools to
                                synchronize
                                data.</p>
                        </div>

                        <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl md:col-span-2">
                            <div class="px-4 py-6 sm:p-8">
                                <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                    <div class="col-span-full">
                                        <h3 class="text-sm font-medium text-slate-900 mb-4">Perfex CRM</h3>
                                        <div class="grid grid-cols-1 gap-y-6">
                                            <div>
                                                <label for="perfex_api_url"
                                                    class="block text-sm font-medium leading-6 text-slate-900">API
                                                    URL</label>
                                                <div class="mt-2">
                                                    <input type="url" name="perfex_api_url" id="perfex_api_url"
                                                        value="{{ old('perfex_api_url', $settings['perfex_api_url'] ?? '') }}"
                                                        placeholder="https://your-perfex-install.com/api/"
                                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500">Must end with /api/ and use
                                                    HTTPS.</p>
                                            </div>

                                            <div>
                                                <label for="perfex_api_token"
                                                    class="block text-sm font-medium leading-6 text-slate-900">API
                                                    Token</label>
                                                <div class="mt-2 text-password-container" x-data="{ show: false }">
                                                    <div class="relative">
                                                        <input :type="show ? 'text' : 'password'"
                                                            name="perfex_api_token" id="perfex_api_token"
                                                            value="{{ old('perfex_api_token', $settings['perfex_api_token'] ?? '') }}"
                                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                        <button type="button" @click="show = !show"
                                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                                            <svg x-show="!show" class="h-5 w-5" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="1.5"
                                                                    d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.791 0 8.601 3.049 9.964 6.322a1.012 1.012 0 010 .644C20.601 15.951 16.791 19 12 19c-4.791 0-8.601-3.049-9.964-6.322z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="1.5"
                                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            <svg x-show="show" class="h-5 w-5" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor"
                                                                style="display: none;">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="1.5"
                                                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M15 15l-3-3m0 0l-3-3m3 3L9 15m3-3l3-3M3 3l18 18" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500">API key from Perfex Setup -> API.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-6 border-t border-slate-900/10 pt-4 px-4 sm:px-0">
                        <button type="button" class="text-sm font-semibold leading-6 text-slate-900">Cancel</button>
                        <button type="submit"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save
                            All Settings</button>
                    </div>
        </form>
    </div>
</x-app-layout>