<x-app-layout>
    <div class="h-[calc(100vh-64px)] flex flex-col">
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Create PDF Template</h1>
            <div class="flex gap-2">
                <a href="{{ route('pdf-templates.index') }}"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" form="templateForm"
                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700">Save
                    Template</button>
            </div>
        </div>

        <form id="templateForm" action="{{ route('pdf-templates.store') }}" method="POST"
            class="flex-1 flex overflow-hidden" x-data="templateEditor()">
            @csrf

            <!-- Sidebar / Settings -->
            <div class="w-80 bg-gray-50 border-r border-gray-200 flex flex-col overflow-y-auto">
                <div class="p-4 space-y-4">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="system_template_id" class="block text-sm font-medium text-gray-700">Start from
                            Template (Optional)</label>
                        <select id="system_template_id" name="system_template_id" x-model="selectedTemplate"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Blank Template</option>
                            @foreach($systemTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select a pre-made design to start with.</p>
                    </div>

                    <!-- Hidden fields removed as we populate editor directly -->

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Template Name</label>
                        <input type="text" name="name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked
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

                    <!-- Security & Control -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Security & Control</h3>

                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" id="is_locked" name="is_locked" value="1"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_locked" class="ml-2 block text-sm text-gray-900">Lock Template <span
                                        class="text-xs text-gray-500">(Admins Only)</span></label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="is_password_protected" name="is_password_protected" value="1"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_password_protected" class="ml-2 block text-sm text-gray-900">Password Protect
                                    <br><span class="text-xs text-gray-500">(Uses Client Email)</span></label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Watermark Text</label>
                                <input type="text" name="watermark_text" x-model="watermarkText"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    placeholder="e.g. DRAFT">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Watermark Opacity</label>
                                <div class="flex items-center gap-2">
                                    <input type="range" name="watermark_opacity" x-model="watermarkOpacity" min="0" max="1" step="0.1"
                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-200">



                    <hr class="border-gray-200">

                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Variables Cheat
                            Sheet</h3>
                        <div
                            class="text-xs text-gray-600 space-y-1 font-mono bg-gray-100 p-2 rounded max-h-96 overflow-y-auto">
                            <div class="font-bold text-gray-800 mt-2">Estimate Details</div>
                            <div>{estimate_number}</div>
                            <div>{estimate_title}</div>
                            <div>{estimate_date}</div>
                            <div>{expiry_date}</div>
                            <div>{status}</div>
                            <div>{currency}</div>

                            <div class="font-bold text-gray-800 mt-2">Financials</div>
                            <div>{subtotal}</div>
                            <div>{discount_total}</div>
                            <div>{tax_total}</div>
                            <div>{grand_total}</div>

                            <div class="font-bold text-gray-800 mt-2">Client Info</div>
                            <div>{client_name}</div>
                            <div>{client_email}</div>
                            <div>{client_address}</div>
                            <div>{client_id}</div>

                            <div class="font-bold text-gray-800 mt-2">Company Info</div>
                            <div>{company_name}</div>
                            <div>{company_email}</div>
                            <div>{company_phone}</div>
                            <div>{company_address}</div>
                            <div>{company_city}</div>

                            <div class="font-bold text-gray-800 mt-2">Notes & Terms</div>
                            <div>{client_note}</div>
                            <div>{terms}</div>
                            <div>{admin_note}</div>

                            <div class="font-bold text-gray-800 mt-2">Items Loop</div>
                            <div>{LOOP_ITEMS}...{END_LOOP}</div>
                            <div>{item_name}</div>
                            <div>{item_description}</div>
                            <div>{item_quantity}</div>
                            <div>{item_unit}</div>
                            <div>{item_unit_full}</div>
                            <div>{item_price}</div>
                            <div>{item_subtotal}</div>
                            <div>{item_tax_percent}</div>
                            <div>{item_total}</div>
                            <div class="text-indigo-600 font-bold">{item_comments}</div>

                            <div class="font-bold text-gray-800 mt-2">Sections (Room Based)</div>
                            <div>{LOOP_SECTIONS}...{END_LOOP}</div>
                            <div>{section_name}</div>
                            <div>{section_subtotal}</div>

                            <div class="font-bold text-gray-800 mt-2">Logic Blocks</div>
                            <div>{IF_variable}...{END_IF}</div>
                            <div>{IF_NOT_variable}...{END_IF}</div>
                            <div>{CHART_ROOMS}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Editor -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Tabs -->
                <div class="bg-gray-100 border-b border-gray-200 flex">
                    <button type="button" @click="activeTab = 'visual'"
                        :class="{'bg-white border-b-2 border-indigo-500 text-indigo-600': activeTab === 'visual', 'text-gray-500 hover:text-gray-700': activeTab !== 'visual'}"
                        class="px-4 py-2 text-sm font-medium">Visual Builder</button>
                    <button type="button" @click="activeTab = 'html'"
                        :class="{'bg-white border-b-2 border-indigo-500 text-indigo-600': activeTab === 'html', 'text-gray-500 hover:text-gray-700': activeTab !== 'html'}"
                        class="px-4 py-2 text-sm font-medium">HTML Code</button>
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

                                <div
                                    class="px-4 py-2 text-xs font-bold text-gray-500 uppercase border-t border-gray-100">
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
                    <!-- Visual Builder UI -->
                    <div x-show="activeTab === 'visual'" class="absolute inset-0 bg-gray-50 flex overflow-hidden">
                        <!-- Block Palette -->
                        <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto flex flex-col">
                            <div class="p-3 bg-gray-100 border-b border-gray-200 font-semibold text-xs text-gray-500 uppercase">Available Blocks</div>
                            <div class="p-2 space-y-2">
                                <template x-for="(block, type) in availableBlocks" :key="type">
                                    <div @click="addBlock(type)" class="cursor-pointer border border-gray-200 rounded p-3 hover:bg-indigo-50 hover:border-indigo-300 transition-colors shadow-sm bg-white">
                                        <div class="font-medium text-sm text-gray-800" x-text="block.label"></div>
                                        <div class="text-xs text-gray-500" x-text="block.desc"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Canvas / Stack -->
                        <div class="flex-1 overflow-y-auto p-8 bg-gray-100 pb-32"> <!-- Added padding bottom for scrolling -->
                             <div class="max-w-3xl mx-auto space-y-4" id="sortable-blocks">
                                <template x-for="(block, index) in blocks" :key="block.id">
                                    <div class="bg-white border rounded shadow-sm relative group"
                                        :class="{'border-indigo-500 ring-2 ring-indigo-200': selectedBlockIndex === index, 'border-gray-200 hover:border-gray-300': selectedBlockIndex !== index}"
                                        @click="selectedBlockIndex = index">
                                        
                                        <!-- Block Header / Actions -->
                                        <div class="flex items-center justify-between p-2 bg-gray-50 border-b border-gray-100 rounded-t cursor-move drag-handle">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                                                <span class="text-xs font-bold uppercase text-gray-500" x-text="availableBlocks[block.type].label"></span>
                                            </div>
                                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button type="button" @click.stop="duplicateBlock(index)" class="p-1 hover:bg-blue-100 rounded text-blue-600" title="Duplicate">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                </button>
                                                <button type="button" @click.stop="removeBlock(index)" class="p-1 hover:bg-red-100 rounded text-red-600" title="Delete">✕</button>
                                            </div>
                                        </div>

                                        <!-- Block Properties Form -->
                                        <div class="p-4" x-show="selectedBlockIndex === index">
                                            <div class="grid grid-cols-1 gap-3">
                                                <template x-for="(fieldDef, fieldName) in availableBlocks[block.type].fields" :key="fieldName">
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-700 mb-1" x-text="fieldDef.label"></label>
                                                        
                                                        <template x-if="fieldDef.type === 'text'">
                                                            <input type="text" x-model="block.data[fieldName]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                                                        </template>
                                                        
                                                        <template x-if="fieldDef.type === 'textarea'">
                                                            <textarea x-model="block.data[fieldName]" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"></textarea>
                                                        </template>

                                                        <template x-if="fieldDef.type === 'richtext'">
                                                    <div class="border border-gray-300 rounded-md overflow-hidden bg-white">
                                                         <!-- Toolbar -->
                                                         <div class="flex items-center gap-1 p-1 bg-gray-50 border-b border-gray-200">
                                                             <button type="button" @click="document.execCommand('bold', false, null)" class="p-1 hover:bg-gray-200 rounded text-gray-700 font-bold" title="Bold">B</button>
                                                             <button type="button" @click="document.execCommand('italic', false, null)" class="p-1 hover:bg-gray-200 rounded text-gray-700 italic" title="Italic">I</button>
                                                             <button type="button" @click="document.execCommand('underline', false, null)" class="p-1 hover:bg-gray-200 rounded text-gray-700 underline" title="Underline">U</button>
                                                             <div class="w-px h-4 bg-gray-300 mx-1"></div>
                                                             <button type="button" @click="document.execCommand('insertUnorderedList', false, null)" class="p-1 hover:bg-gray-200 rounded text-gray-700" title="Bulleted List">• List</button>
                                                             <button type="button" @click="document.execCommand('justifyLeft', false, null)" class="p-1 hover:bg-gray-200 rounded text-gray-700" title="Align Left">←</button>
                                                             <button type="button" @click="document.execCommand('justifyCenter', false, null)" class="p-1 hover:bg-gray-200 rounded text-gray-700" title="Align Center">↔</button>
                                                         </div>
                                                         <!-- Editor -->
                                                         <div 
                                                            contenteditable="true" 
                                                            class="p-3 min-h-[100px] outline-none text-sm"
                                                            @input="block.data[fieldName] = $el.innerHTML"
                                                            x-html="block.data[fieldName] || ''">
                                                         </div>
                                                    </div>
                                                </template>

                                                <template x-if="fieldDef.type === 'color'">
                                                            <div class="flex items-center gap-2">
                                                                <input type="color" x-model="block.data[fieldName]" class="h-6 w-8 p-0 border rounded">
                                                                <input type="text" x-model="block.data[fieldName]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                                                            </div>
                                                        </template>

                                                         <template x-if="fieldDef.type === 'select'">
                                                            <select x-model="block.data[fieldName]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                                                                <template x-for="opt in fieldDef.options" :key="opt">
                                                                     <option :value="opt" x-text="opt"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        
                                                        <template x-if="fieldDef.type === 'checkbox'">
                                                            <div class="flex items-center">
                                                                <input type="checkbox" x-model="block.data[fieldName]" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                                <span class="ml-2 text-sm text-gray-600" x-text="fieldDef.label"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        
                                        <!-- Minimized Preview (when not selected) -->
                                        <div class="p-2 text-sm text-gray-400 italic" x-show="selectedBlockIndex !== index">
                                            Click to edit settings...
                                        </div>
                                    </div>
                                </template>
                                
                                <div x-show="blocks.length === 0" class="text-center py-10 text-gray-400 border-2 border-dashed border-gray-300 rounded-lg">
                                    <p>No blocks added yet.</p>
                                    <p class="text-xs">Click a block from the left to start.</p>
                                </div>
                             </div>
                        </div>
                        
                        <!-- Hidden input to store structure -->
                        <input type="hidden" name="content_structure" :value="JSON.stringify(blocks)">
                    </div>

                    <div x-show="activeTab === 'html'" class="absolute inset-0">
                        <textarea id="htmlEditor" name="html_content" x-model="html"
                            class="w-full h-full p-4 font-mono text-sm bg-gray-900 text-gray-100 resize-none focus:ring-0 border-0"
                            spellcheck="false">{{ old('html_content', '<html><body><h1>New Template</h1>\n\n<p>Start designing your invoice...</p>\n\n<footer style="position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 12px; color: #777;">\n    &copy; {company_name}. All rights reserved.\n</footer></body></html>') }}</textarea>
                    </div>
                    <div x-show="activeTab === 'css'" class="absolute inset-0">
                        <textarea id="cssEditor" name="css_content" x-model="css"
                            class="w-full h-full p-4 font-mono text-sm bg-gray-900 text-gray-100 resize-none focus:ring-0 border-0"
                            spellcheck="false">{{ old('css_content') }}</textarea>
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('templateEditor', () => ({
                    activeTab: 'visual', // Default to Visual
                    html: `{{ old('html_content', '<html><body><h1>New Template</h1>\n\n<p>Start designing your invoice...</p>\n\n<footer style="position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 12px; color: #777;">\n    &copy; {company_name}. All rights reserved.\n</footer></body></html>') }}`, 
                    css: `{{ old('css_content') }}`,
                    paperSize: 'a4',
                    orientation: 'portrait',
                    primaryColor: '#333333',
                    secondaryColor: '#555555',
                    fontFamily: 'Helvetica',
                    watermarkText: '',
                    watermarkOpacity: '0.1',
                    
                    // Visual Builder Data
                    blocks: [], // [ {id: 1, type: 'header', data: {...}} ]
                    selectedBlockIndex: null,
                    availableBlocks: {
                        header: {
                            label: 'Company Header',
                            desc: 'Logo, Name, Address',
                            fields: {
                                showLogo: { label: 'Show Logo', type: 'checkbox' },
                                align: { label: 'Alignment', type: 'select', options: ['left', 'center', 'right'] },
                                bgColor: { label: 'Background', type: 'color' },
                                // Advanced Styling
                                paddingTop: { label: 'Padding Top (px)', type: 'text' },
                                paddingBottom: { label: 'Padding Bottom (px)', type: 'text' }
                            },
                            render: (data) => `<div style="padding: ${data.paddingTop || '20'}px 20px ${data.paddingBottom || '20'}px; background-color: ${data.bgColor || 'transparent'}; text-align: ${data.align || 'left'};">
                                ${data.showLogo ? '<img src="https://placehold.co/100x50?text=LOGO" style="margin-bottom:10px;">' : ''}
                                <h1 style="margin:0; font-size: 24px; color: var(--primary-color);">{company_name}</h1>
                                <p style="margin: 5px 0; font-size: 12px; color: #555;">{company_address} | {company_email}</p>
                            </div>`
                        },
                        columns_2: {
                            label: '2 Columns',
                            desc: 'Split content 50/50',
                            fields: {
                                col1_title: { label: 'Left Title', type: 'text' },
                                col1_content: { label: 'Left Content', type: 'textarea' },
                                col2_title: { label: 'Right Title', type: 'text' },
                                col2_content: { label: 'Right Content', type: 'textarea' }
                            },
                            render: (data) => `<div style="display: flex; gap: 20px; padding: 20px;">
                                <div style="flex: 1;">
                                    ${data.col1_title ? `<h3 style="border-bottom: 2px solid var(--secondary-color); font-size: 14px; margin-bottom: 10px;">${data.col1_title}</h3>` : ''}
                                    <div>${data.col1_content || 'Left column content...'}</div>
                                </div>
                                <div style="flex: 1;">
                                    ${data.col2_title ? `<h3 style="border-bottom: 2px solid var(--secondary-color); font-size: 14px; margin-bottom: 10px;">${data.col2_title}</h3>` : ''}
                                    <div>${data.col2_content || 'Right column content...'}</div>
                                </div>
                            </div>`
                        },
                        image: {
                            label: 'Image',
                            desc: 'Insert via URL',
                            fields: {
                                src: { label: 'Image URL', type: 'text', placeholder: 'https://...' },
                                width: { label: 'Width (px or %)', type: 'text' },
                                align: { label: 'Alignment', type: 'select', options: ['left', 'center', 'right'] }
                            },
                            render: (data) => `<div style="padding: 20px; text-align: ${data.align || 'center'};">
                                <img src="${data.src || 'https://placehold.co/400x200?text=Placeholder+Image'}" style="max-width: 100%; width: ${data.width || 'auto'};">
                            </div>`
                        },
                        chart: {
                            label: 'Chart',
                            desc: 'Dynamic graph',
                            fields: {
                                type: { label: 'Chart Type', type: 'select', options: ['Pie', 'Doughnut', 'Bar'] },
                                source: { label: 'Data Source', type: 'select', options: ['Sections', 'Items'] },
                                align: { label: 'Alignment', type: 'select', options: ['left', 'center', 'right'] }
                            },
                            render: (data) => {
                                const type = (data.type || 'Pie').toUpperCase();
                                const source = (data.source || 'Sections').toUpperCase();
                                const variable = `{CHART_${source}_${type}}`;
                                
                                return `<div style="padding: 20px; text-align: ${data.align || 'center'};">
                                    <h3 style="margin-bottom: 10px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--secondary-color);">${data.source || 'Sections'} ${data.type || 'Chart'}</h3>
                                    <img src="${variable}" style="max-width: 100%; width: 500px; height: auto;">
                                    <p style="font-size: 11px; color: #888; font-style: italic; margin-top: 5px;">* Chart data rendered dynamically in PDF</p>
                                </div>`;
                            }
                        },
                        client_info: {
                            label: 'Client Info',
                            desc: 'Bill To / Ship To columns',
                            fields: {
                                title: { label: 'Section Title', type: 'text' }
                            },
                            render: (data) => `<div style="padding: 20px; display: flex; gap: 20px;">
                                <div style="flex: 1;">
                                    <h3 style="border-bottom: 2px solid var(--secondary-color); padding-bottom: 5px; margin-bottom: 10px; font-size: 14px;">${data.title || 'Bill To'}</h3>
                                    <p><strong>{client_name}</strong><br>{client_address}<br>{client_email}</p>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="border-bottom: 2px solid var(--secondary-color); padding-bottom: 5px; margin-bottom: 10px; font-size: 14px;">Estimate Details</h3>
                                    <p><strong>#:</strong> {estimate_number}<br><strong>Date:</strong> {estimate_date}<br><strong>Expires:</strong> {expiry_date}</p>
                                </div>
                            </div>`
                        },
                        items_table: {
                            label: 'Items Table',
                            desc: 'Dynamic list of items',
                            fields: {},
                            render: (data) => `<div style="padding: 0 20px;">
                                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                                    <thead>
                                        <tr style="background-color: var(--primary-color); color: white;">
                                            <th style="padding: 10px; text-align: left;">Item</th>
                                            <th style="padding: 10px; text-align: right;">Price</th>
                                            <th style="padding: 10px; text-align: right;">Qty</th>
                                            <th style="padding: 10px; text-align: right;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {LOOP_ITEMS}
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 10px;"><strong>{item_name}</strong><br><span style="font-size: 12px; color: #777;">{item_description}</span></td>
                                            <td style="padding: 10px; text-align: right;">{item_price}</td>
                                            <td style="padding: 10px; text-align: right;">{item_quantity}</td>
                                            <td style="padding: 10px; text-align: right;">{item_total}</td>
                                        </tr>
                                        {END_LOOP}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">Subtotal:</td>
                                            <td style="padding: 10px; text-align: right;">{subtotal}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" style="padding: 10px; text-align: right; font-weight: bold; color: var(--primary-color); font-size: 16px;">Grand Total:</td>
                                            <td style="padding: 10px; text-align: right; font-weight: bold; color: var(--primary-color); font-size: 16px;">{grand_total}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>`
                        },
                        text_block: {
                            label: 'Text Block',
                            desc: 'Rich text area',
                            fields: {
                                content: { label: 'Content', type: 'richtext' },
                                // Typography
                                fontSize: { label: 'Font Size (px)', type: 'text' },
                                fontWeight: { label: 'Weight', type: 'select', options: ['normal', 'bold', '300', '500', '700'] },
                                textColor: { label: 'Text Color', type: 'color' },
                                // Spacing & Border
                                paddingTop: { label: 'Pad Top', type: 'text' },
                                paddingBottom: { label: 'Pad Bot', type: 'text' },
                                border: { label: 'Border (e.g. 1px solid #ccc)', type: 'text' },
                                borderRadius: { label: 'Radius (px)', type: 'text' }
                            },
                            render: (data) => `<div style="
                                padding: ${data.paddingTop || '20'}px 20px ${data.paddingBottom || '20'}px; 
                                font-size: ${data.fontSize || '14'}px; 
                                font-weight: ${data.fontWeight || 'normal'}; 
                                color: ${data.textColor || 'inherit'};
                                border: ${data.border || 'none'};
                                border-radius: ${data.borderRadius || '0'}px;
                            ">${data.content || 'Enter text here...'}</div>`
                        },
                         spacer: {
                            label: 'Spacer',
                            desc: 'Vertical gap',
                            fields: {
                                height: { label: 'Height (px)', type: 'text' }
                            },
                            render: (data) => `<div style="height: ${data.height || '20'}px;"></div>`
                        },
                        divider: {
                            label: 'Divider',
                            desc: 'Horizontal Line',
                            fields: {
                                color: { label: 'Color', type: 'color' },
                                width: { label: 'Thickness (px)', type: 'text' },
                                style: { label: 'Style', type: 'select', options: ['solid', 'dashed', 'dotted'] },
                                margin: { label: 'Margin Y (px)', type: 'text' }
                            },
                            render: (data) => `<div style="padding: ${data.margin || '20'}px 0;">
                                <hr style="border: 0; border-top: ${data.width || '1'}px ${data.style || 'solid'} ${data.color || '#e5e7eb'};">
                            </div>`
                        },
                        footer: {
                            label: 'Footer',
                            desc: 'Terms & Notes',
                            fields: {
                                showTerms: { label: 'Show Terms', type: 'checkbox' },
                                centerText: { label: 'Center Text', type: 'text' }
                            },
                            render: (data) => `<div style="padding: 20px; margin-top: 30px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                                ${data.showTerms ? '<h4>Terms & Conditions</h4><p>{terms}</p>' : ''}
                                <div style="text-align: center; margin-top: 20px;">${data.centerText || ''}</div>
                            </div>`
                        }
                    },

                    debounceTimer: null,
                    systemTemplates: @json($systemTemplates),
                    
                    init() {
                        // Decide text or visual tab initially
                        if (this.blocks && this.blocks.length > 0) {
                            this.activeTab = 'visual';
                        } else {
                           this.activeTab = 'visual';
                        }

                        // Watch for compiles
                        this.$watch('blocks', () => this.compileBlocks(), { deep: true });

                        this.$watch('html', () => this.updatePreview());
                        this.$watch('css', () => this.updatePreview());
                        // Watch styling properties to update preview instantly
                        this.$watch('primaryColor', () => this.updatePreview());
                        this.$watch('secondaryColor', () => this.updatePreview());
                        this.$watch('fontFamily', () => this.updatePreview());
                        this.$watch('watermarkText', () => this.updatePreview());
                        this.$watch('watermarkOpacity', () => this.updatePreview());

                        this.$watch('activeTab', (val) => {
                            if (val === 'preview') this.updatePreview();
                        });
                        
                        // Initialize SortableJS
                        this.$nextTick(() => {
                            if (document.getElementById('sortable-blocks')) {
                                new Sortable(document.getElementById('sortable-blocks'), {
                                    animation: 150,
                                    handle: '.drag-handle',
                                    onEnd: (evt) => {
                                        // Re-order active blocks based on DOM order
                                        // This is a bit tricky with Alpine reactivity, so we'll do a simple swap or move
                                        // Actually, simpler: let's just grab the indices
                                        const itemEl = evt.item;
                                        const newIndex = evt.newIndex;
                                        const oldIndex = evt.oldIndex;
                                        
                                        if (newIndex === oldIndex) return;
                                        
                                        // Move in array
                                        const movedItem = this.blocks.splice(oldIndex, 1)[0];
                                        this.blocks.splice(newIndex, 0, movedItem);
                                        
                                        // Fix selected index if needed
                                        if (this.selectedBlockIndex === oldIndex) {
                                            this.selectedBlockIndex = newIndex;
                                        } else if (oldIndex < this.selectedBlockIndex && newIndex >= this.selectedBlockIndex) {
                                            this.selectedBlockIndex--;
                                        } else if (oldIndex > this.selectedBlockIndex && newIndex <= this.selectedBlockIndex) {
                                            this.selectedBlockIndex++;
                                        }
                                        
                                        this.compileBlocks();
                                    }
                                });
                            }
                        });

                        // Default blocks for new template
                         if (!this.html) { // Only if blank
                             this.addBlock('header');
                             this.addBlock('client_info');
                             this.addBlock('items_table');
                             this.addBlock('footer');
                         }
                         
                         // If old input for content_structure exists (e.g. valid err), load it. But here we are just initing.
                         
                         setTimeout(() => this.updatePreview(), 500);
                    },

                    addBlock(type) {
                        this.blocks.push({
                            id: Date.now(),
                            type: type,
                            data: { bgColor: '#ffffff', align: 'left' } // Defaults
                        });
                        this.selectedBlockIndex = this.blocks.length - 1;
                        this.compileBlocks();
                    },

                    removeBlock(index) {
                        this.blocks.splice(index, 1);
                        this.selectedBlockIndex = null;
                        this.compileBlocks();
                    },
                    duplicateBlock(index) {
                        const blockToClone = this.blocks[index];
                        const newBlock = {
                            id: Date.now(), // Unique ID
                            type: blockToClone.type,
                            data: JSON.parse(JSON.stringify(blockToClone.data)) // Deep copy
                        };
                        this.blocks.splice(index + 1, 0, newBlock); // Insert after
                        this.selectedBlockIndex = index + 1;
                        this.compileBlocks();
                    },
                    moveBlock(index, direction) {
                         if (index + direction < 0 || index + direction >= this.blocks.length) return;
                         const temp = this.blocks[index];
                         this.blocks[index] = this.blocks[index + direction];
                         this.blocks[index + direction] = temp;
                         this.selectedBlockIndex = index + direction; // Follow selection
                         // Trigger update
                         this.blocks = [...this.blocks]; 
                         this.compileBlocks();
                    },

                    compileBlocks() {
                        // Generate HTML from blocks
                        let compiledHtml = `<html><body>`;
                        
                        this.blocks.forEach(block => {
                            const def = this.availableBlocks[block.type];
                            if (def) {
                                compiledHtml += `<!-- BLOCK: ${block.type} -->\n`;
                                compiledHtml += def.render(block.data) + '\n';
                            }
                        });

                        compiledHtml += `</body></html>`;
                        
                        this.html = compiledHtml;
                        // No need for updatePreview() explicitly as this.html watcher will trigger it
                    },

                    selectedTemplate: '',

                    loadSystemTemplate(id) {
                        if (!id) return;
                        const t = this.systemTemplates.find(x => x.id == id);
                        if (t) {
                           this.html = t.html_content;
                           this.css = t.css_content;
                           this.primaryColor = t.primary_color;
                           this.secondaryColor = t.secondary_color;
                           this.fontFamily = t.font_family;
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

                                // Inject Styling Variables for Live Preview (without reloading from server necessarily, 
                                // but our server preview endpoint uses CSS from body request, so just relying on server render is safer)
                                // However, let's inject a style block to override root vars instantly for faster feedback
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