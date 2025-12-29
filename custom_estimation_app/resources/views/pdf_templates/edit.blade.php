<x-app-layout>
    <div class="h-[calc(100vh-64px)] flex flex-col">
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Edit PDF Template: {{ $pdfTemplate->name }}</h1>
            <div class="flex gap-2">
                <button type="button" @click="$dispatch('open-history')"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    History
                </button>
                <a href="{{ route('pdf-templates.index') }}"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" form="templateForm"
                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700">Update
                    Template</button>
            </div>
        </div>

        <form id="templateForm" action="{{ route('pdf-templates.update', $pdfTemplate) }}" method="POST"
            class="flex-1 flex overflow-hidden" x-data="templateEditor()">
            @csrf
            @method('PUT')

            <!-- Sidebar / Settings -->
            <div class="w-80 bg-gray-50 border-r border-gray-200 flex flex-col overflow-y-auto">
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Template Name</label>
                        <input type="text" name="name" value="{{ old('name', $pdfTemplate->name) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $pdfTemplate->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                    </div>

                    <hr class="border-gray-200">

                    <!-- PDF Settings -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Paper Size</label>
                        <select name="paper_size" x-model="paperSize"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="a4">A4 (210mm x 297mm)</option>
                            <option value="letter">Letter (216mm x 279mm)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Orientation</label>
                        <select name="orientation" x-model="orientation"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="portrait">Portrait</option>
                            <option value="landscape">Landscape</option>
                        </select>
                    </div>

                    <hr class="border-gray-200">

                    <!-- Styling Settings -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Primary Color</label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input type="color" name="primary_color" x-model="primaryColor"
                                class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                            <input type="text" x-model="primaryColor"
                                class="flex-1 block w-full rounded-md border-gray-300 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Secondary Color</label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input type="color" name="secondary_color" x-model="secondaryColor"
                                class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                            <input type="text" x-model="secondaryColor"
                                class="flex-1 block w-full rounded-md border-gray-300 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Font Family</label>
                        <select name="font_family" x-model="fontFamily"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="Helvetica">Helvetica (Sans-serif)</option>
                            <option value="Courier">Courier (Monospace)</option>
                            <option value="Times">Times New Roman (Serif)</option>
                            <option value="Dejavu Sans">Dejavu Sans (UTF-8 support)</option>
                        </select>
                    </div>

                    <hr class="border-gray-200">

                    <!-- Theme Presets -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Load Theme Preset</label>
                        <select @change="loadTheme($event.target.value); $event.target.value=''"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select a Theme...</option>
                            <option value="modern">Modern (Default)</option>
                            <option value="classic">Classic</option>
                            <option value="minimal">Minimal</option>
                        </select>
                    </div>

                    <hr class="border-gray-200">

                <!-- Security & Control -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Security & Control
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_locked" name="is_locked" value="1" {{ old('is_locked', $pdfTemplate->is_locked) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="is_locked" class="ml-2 block text-sm text-gray-900">Lock Template <span
                                    class="text-xs text-gray-500">(Admins Only)</span></label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="is_password_protected" name="is_password_protected" value="1" {{ old('is_password_protected', $pdfTemplate->is_password_protected) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="is_password_protected" class="ml-2 block text-sm text-gray-900">Password Protect
                                <br><span class="text-xs text-gray-500">(Uses Client Email)</span></label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Watermark Text</label>
                            <input type="text" name="watermark_text" x-model="watermarkText"
                                value="{{ old('watermark_text', $pdfTemplate->watermark_text) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="e.g. DRAFT">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Watermark Opacity</label>
                            <div class="flex items-center gap-2">
                                <input type="range" name="watermark_opacity" x-model="watermarkOpacity" min="0" max="1" step="0.1"
                                    value="{{ old('watermark_opacity', $pdfTemplate->watermark_opacity) }}"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200">

                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Variables Cheat
                        Sheet</h3>
                    <div
                        class="text-xs text-gray-600 space-y-1 font-mono bg-gray-100 p-2 rounded max-h-96 overflow-y-auto">
                        <div class="font-bold text-gray-800 mt-2">Estimate</div>
                        <div>{estimate_number}</div>
                        <div>{estimate_date}</div>
                        <div>{expiry_date}</div>
                        <div>{subtotal}</div>
                        <div>{grand_total}</div>

                        <div class="font-bold text-gray-800 mt-2">Client</div>
                        <div>{client_name}</div>
                        <div>{client_email}</div>
                        <div>{client_address}</div>

                        <div class="font-bold text-gray-800 mt-2">Logic</div>
                        <div>{LOOP_ITEMS}...{END_LOOP}</div>
                        <div>{item_name}</div>
                        <div>{item_price}</div>
                        <div>{item_total}</div>
                        <div>{IF_HAS_DISCOUNT}...</div>
                    </div>
                </div>
            </div>
    </div>

    <!-- Editor -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Tabs -->
        <div class="bg-gray-100 border-b border-gray-200 flex">
            <button type="button" @click="activeTab = 'html'"
                :class="{'bg-white border-b-2 border-indigo-500 text-indigo-600': activeTab === 'html', 'text-gray-500 hover:text-gray-700': activeTab !== 'html'}"
                class="px-4 py-2 text-sm font-medium">HTML</button>
            <button type="button" @click="activeTab = 'css'"
                :class="{'bg-white border-b-2 border-indigo-500 text-indigo-600': activeTab === 'css', 'text-gray-500 hover:text-gray-700': activeTab !== 'css'}"
                class="px-4 py-2 text-sm font-medium">CSS</button>
            <button type="button" @click="activeTab = 'preview'"
                :class="{'bg-white border-b-2 border-indigo-500 text-indigo-600': activeTab === 'preview', 'text-gray-500 hover:text-gray-700': activeTab !== 'preview'}"
                class="px-4 py-2 text-sm font-medium">Live Preview</button>
        </div>

        <!-- Toolbar -->
        <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center gap-2"
            x-show="activeTab !== 'preview'">
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    Insert Snippet
                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open"
                    class="origin-top-left absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
                    style="display: none;">
                    <div class="py-1">
                        <button type="button" @click="insertSnippet('table'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Item
                            Table</button>
                        <button type="button" @click="insertSnippet('2col'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">2
                            Columns</button>
                        <button type="button" @click="insertSnippet('img'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Image</button>
                        <button type="button" @click="insertSnippet('header_footer'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">CSS
                            Header/Footer</button>
                        <button type="button" @click="insertSnippet('watermark'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Watermark</button>
                        <button type="button" @click="insertSnippet('pagenumber'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Page
                            Numbers (CSS)</button>
                        <button type="button" @click="insertSnippet('logic_if'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logic:
                            IF Block</button>
                        <button type="button" @click="insertSnippet('page_break'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Page
                            Break</button>
                    </div>
                </div>
            </div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    Insert Variable
                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open"
                    class="origin-top-left absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
                    style="display: none;">
                    <div class="py-1 max-h-60 overflow-y-auto">
                        <div class="px-4 py-2 text-xs font-bold text-gray-500 uppercase">Estimate</div>
                        <button type="button" @click="insertAtCursor('{estimate_number}'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{estimate_number}</button>
                        <button type="button" @click="insertAtCursor('{estimate_date}'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{estimate_date}</button>
                        <button type="button" @click="insertAtCursor('{grand_total}'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{grand_total}</button>

                        <div class="px-4 py-2 text-xs font-bold text-gray-500 uppercase border-t border-gray-100">
                            Client</div>
                        <button type="button" @click="insertAtCursor('{client_name}'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{client_name}</button>
                        <button type="button" @click="insertAtCursor('{client_email}'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{client_email}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Code Areas -->
        <div class="flex-1 relative">
            <div x-show="activeTab === 'html'" class="absolute inset-0">
                <textarea id="htmlEditor" name="html_content" x-model="html"
                    class="w-full h-full p-4 font-mono text-sm bg-gray-900 text-gray-100 resize-none focus:ring-0 border-0"
                    spellcheck="false"></textarea>
            </div>
            <div x-show="activeTab === 'css'" class="absolute inset-0">
                <textarea id="cssEditor" name="css_content" x-model="css"
                    class="w-full h-full p-4 font-mono text-sm bg-gray-900 text-gray-100 resize-none focus:ring-0 border-0"
                    spellcheck="false"></textarea>
            </div>

            <!-- Preview Pane -->
            <div x-show="activeTab === 'preview'"
                class="absolute inset-0 bg-gray-200 p-4 flex justify-center overflow-auto">
                <div class="bg-white shadow-lg transition-all duration-300 transform origin-top"
                    :style="getPreviewStyle()">
                    <iframe id="previewFrame" class="w-full h-full border-0"></iframe>
                </div>
            </div>
        </div>
    </div>
    </form>
    </div>

    @push('scripts')
    <!-- History Modal -->
    <div x-data="{ open: false }" @open-history.window="open = true" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="open" style="display: none;">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6"
                    x-show="open" @click.outside="open = false"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Version History</h3>
                            <div class="mt-2 text-left">
                                <ul role="list" class="divide-y divide-gray-100 max-h-60 overflow-y-auto">
                                    @foreach($pdfTemplate->versions as $version)
                                    <li class="flex justify-between gap-x-6 py-3">
                                        <div class="flex min-w-0 gap-x-4">
                                            <div class="min-w-0 flex-auto">
                                                <p class="text-sm font-semibold leading-6 text-gray-900">Version {{ $version->version }}</p>
                                                <p class="mt-1 truncate text-xs leading-5 text-gray-500">By {{ $version->creator->name ?? 'Unknown' }} • {{ $version->created_at->format('M d, Y H:i') }}</p>
                                            </div>
                                        </div>
                                        <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                                            <form action="{{ route('pdf-templates.restore', ['pdfTemplate' => $pdfTemplate->id, 'version' => $version->id]) }}" method="POST" onsubmit="return confirm('Restore this version? current unsaved changes will be lost.');">
                                                @csrf
                                                <button type="submit" class="text-sm font-semibold leading-6 text-indigo-600 hover:text-indigo-500">Restore</button>
                                            </form>
                                        </div>
                                    </li>
                                    @endforeach
                                    @if($pdfTemplate->versions->isEmpty())
                                        <li class="py-4 text-center text-sm text-gray-500">No history available yet.</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('templateEditor', () => ({
                    activeTab: 'html',
                    html: @json(old('html_content', $pdfTemplate->html_content)),
                    css: `{{ old('css_content', $pdfTemplate->css_content) }}`,
                    paperSize: '{{ old('paper_size', $pdfTemplate->paper_size) }}',
                    orientation: '{{ old('orientation', $pdfTemplate->orientation) }}',
                    primaryColor: '{{ old('primary_color', $pdfTemplate->primary_color) }}',
                    secondaryColor: '{{ old('secondary_color', $pdfTemplate->secondary_color) }}',
                    fontFamily: '{{ old('font_family', $pdfTemplate->font_family) }}',
                    watermarkText: '{{ old('watermark_text', $pdfTemplate->watermark_text) }}',
                    watermarkOpacity: '{{ old('watermark_opacity', $pdfTemplate->watermark_opacity) }}',
                    debounceTimer: null,

                    init() {
                        this.$watch('html', () => this.updatePreview());
                        this.$watch('css', () => this.updatePreview());
                        // Watch styling properties to update preview instantly
                        this.$watch('primaryColor', () => this.updatePreview());
                        this.$watch('secondaryColor', () => this.updatePreview());
                        this.$watch('fontFamily', () => this.updatePreview());
                        this.$watch('watermarkText', () => this.updatePreview());
                        this.$watch('watermarkOpacity', () => this.updatePreview());
                        
                        // Watch Manual Input changes (since they aren't x-model assigned to vars used in preview body construction directly in the same way, 
                        // but actually I should bind them to x-model to make this clean. 
                        // For now, let's just use existing x-model if I added it? 
                        // Wait, I only added name attributes, not x-model for watermark.
                        // I should add x-model to watermark inputs.)

                        this.$watch('activeTab', (val) => {
                            if (val === 'preview') this.updatePreview();
                        });

                        // Initial render
                        setTimeout(() => this.updatePreview(), 500);
                    },

                    loadTheme(theme) {
                        if (!confirm('This will overwrite your current HTML and CSS. Continue?')) return;

                        if (theme === 'modern') {
                            this.html = `<h1>Estimate #{estimate_number}</h1>
                                    <div class="header-box" style="background: var(--primary-color); color: white; padding: 20px;">
                                      <h2>{company_name}</h2>
                                    </div>
                                    <div style="margin-top: 20px;">
                                      <strong>Bill To:</strong> {client_name}<br>
                                      {client_email}
                                    </div>
                                    {LOOP_ITEMS}
                                    <div style="border-bottom: 1px solid #eee; padding: 10px 0; display: flex; justify-content: space-between;">
                                      <span>{item_name}</span>
                                      <span>{item_total}</span>
                                    </div>
                                    {END_LOOP}
                                    <div style="text-align: right; margin-top: 20px; font-weight: bold; color: var(--secondary-color);">
                                      Grand Total: {grand_total}
                                    </div>`;
                            this.css = `.header-box { border-radius: 5px; }`;
                        } else if (theme === 'classic') {
                            this.html = `<div style="text-align: center; border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; margin-bottom: 20px;">
                                      <h1 style="color: var(--primary-color);">{company_name}</h1>
                                      <p>Estimate #{estimate_number}</p>
                                    </div>
                                    <table style="width: 100%; border-collapse: collapse;">
                                      <tr style="background: var(--secondary-color); color: white;">
                                        <th style="padding: 10px;">Item</th>
                                        <th style="padding: 10px;">Cost</th>
                                      </tr>
                                      {LOOP_ITEMS}
                                      <tr>
                                        <td style="padding: 10px; border-bottom: 1px solid #ddd;">{item_name}</td>
                                        <td style="padding: 10px; border-bottom: 1px solid #ddd;">{item_total}</td>
                                      </tr>
                                      {END_LOOP}
                                    </table>`;
                            this.css = `table { font-size: 14px; }`;
                        } else if (theme === 'minimal') {
                            this.html = `<h1>{estimate_number}</h1>
                                    <hr style="border-top: 1px dashed #ccc;">
                                    {LOOP_ITEMS}
                                    <p>{item_name} .......................... {item_total}</p>
                                    {END_LOOP}
                                    <h3>Total: {grand_total}</h3>`;
                            this.css = `body { font-family: 'Courier', monospace; }`;
                        }
                    },

                    getPreviewStyle() {
                        // Base dimensions in mm
                        let width, height;
                        if (this.paperSize === 'a4') {
                            width = 210; height = 297;
                        } else { // Letter
                            width = 215.9; height = 279.4;
                        }

                        if (this.orientation === 'landscape') {
                            [width, height] = [height, width];
                        }

                        return `width: ${width}mm; height: ${height}mm; min-height: ${height}mm;`;
                    },

                    updatePreview() {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => {
                            this.fetchPreview();
                        }, 800);
                    },

                    fetchPreview() {
                        const iframe = document.getElementById('previewFrame');
                        if (!iframe) return;

                        fetch('{{ route("pdf-templates.preview") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                html_content: this.html,
                                css_content: this.css,
                                watermark_text: this.watermarkText,
                                watermark_opacity: this.watermarkOpacity,
                                primary_color: this.primaryColor,
                                secondary_color: this.secondaryColor,
                                font_family: this.fontFamily
                            })
                        })
                            .then(res => res.text())
                            .then(html => {
                                const doc = iframe.contentWindow.document;
                                doc.open();
                                doc.write(html);

                                // Inject Styling Variables for Live Preview
                                const style = doc.createElement('style');
                                style.innerHTML = `:root { 
                                                                            --primary-color: ${this.primaryColor}; 
                                                                            --secondary-color: ${this.secondaryColor}; 
                                                                            --font-body: ${this.fontFamily}; 
                                                                        } 
                                                                        body { font-family: var(--font-body) !important; }`;
                                doc.head.appendChild(style);

                                doc.close();
                            })
                            .catch(err => console.error(err));
                    },

                    insertAtCursor(text) {
                        const textarea = this.activeTab === 'html' ? document.getElementById('htmlEditor') : document.getElementById('cssEditor');
                        if (!textarea) return;

                        const start = textarea.selectionStart;
                        const end = textarea.selectionEnd;
                        const val = textarea.value;

                        const newVal = val.substring(0, start) + text + val.substring(end);

                        // Update Alpine model
                        if (this.activeTab === 'html') this.html = newVal;
                        else this.css = newVal;

                        // Restore cursor
                        this.$nextTick(() => {
                            textarea.focus();
                            textarea.selectionStart = textarea.selectionEnd = start + text.length;
                        });
                    },

                    insertSnippet(type) {
                        let snippet = '';
                        if (type === 'table') {
                            snippet = `
                                                        <table class="w-full border-collapse">
                                                            <thead>
                                                                <tr>
                                                                    <th class="border-b p-2 text-left">Item</th>
                                                                    <th class="border-b p-2 text-right">Price</th>
                                                                    <th class="border-b p-2 text-right">Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {LOOP_ITEMS}
                                                                <tr>
                                                                    <td class="border-b p-2">{item_name}</td>
                                                                    <td class="border-b p-2 text-right">{item_price}</td>
                                                                    <td class="border-b p-2 text-right">{item_total}</td>
                                                                </tr>
                                                                {END_LOOP}
                                                            </tbody>
                                                        </table>`;
                        } else if (type === '2col') {
                            snippet = `
                                                        <div style="display: flex; gap: 20px;">
                                                            <div style="flex: 1;">Column 1</div>
                                                            <div style="flex: 1;">Column 2</div>
                                                        </div>`;
                        } else if (type === 'img') {
                            snippet = `<img src="https://placehold.co/150" alt="Placeholder" style="max-width: 100%;">`;
                        } else if (type === 'header_footer') {
                            // Switch to CSS tab if not already
                            if (this.activeTab !== 'css') {
                                alert('Switching to CSS tab to insert @page styles.');
                                this.activeTab = 'css';
                                // Defer slightly
                                setTimeout(() => {
                                    this.insertAtCursor(`@page { margin: 100px 25px; }
                                                        header { position: fixed; top: -60px; left: 0px; right: 0px; height: 50px; }
                                                        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; }`);
                                }, 100);
                                return;
                            }
                            snippet = `@page { margin: 100px 25px; }
                                            header { position: fixed; top: -60px; left: 0px; right: 0px; height: 50px; }
                                            footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; }`;
                        } else if (type === 'watermark') {
                            snippet = `<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 100px; color: #000; opacity: 0.1; white-space: nowrap; z-index: -1;">
                                              DRAFT
                                            </div>`;
                        } else if (type === 'pagenumber') {
                            if (this.activeTab !== 'css') {
                                alert('Switching to CSS for page numbers.');
                                this.activeTab = 'css';
                                setTimeout(() => {
                                    this.insertAtCursor(`.page-number:after { content: counter(page); }`);
                                }, 100);
                                return;
                            }
                            snippet = `.page-number:after { content: counter(page); }`;
                        } else if (type === 'logic_if') {
                            snippet = `{IF_has_discount}\n  <div class="alert">Has Discount!</div>\n{END_IF}`;
                        } else if (type === 'page_break') {
                            snippet = `<div class="page-break-after"></div>`;
                        }


                                                        this.insertAtCursor(snippet);
                }
                                                }));
                                            });
        </script>
    @endpush
</x-app-layout>