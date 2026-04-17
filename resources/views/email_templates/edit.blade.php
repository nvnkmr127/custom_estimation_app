<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="emailEditor()">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('email-templates.index') }}"
                    class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors mb-4">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Templates
                </a>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">
                    {{ isset($emailTemplate) ? 'Edit Template' : 'Create Template' }}
                </h1>
            </div>

            @isset($emailTemplate)
                <form action="{{ route('email-templates.test', $emailTemplate) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-blue-600 text-blue-600 rounded-full hover:bg-blue-50 transition-colors font-medium text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send Test to Inbox
                    </button>
                </form>
            @endisset
        </div>

        <form action="{{ isset($emailTemplate) ? route('email-templates.update', $emailTemplate) : route('email-templates.store') }}" method="POST">
            @csrf
            @isset($emailTemplate) @method('PUT') @endisset

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column: Editor -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Template Name</label>
                                <input type="text" name="name" x-model="name" required class="block w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Estimate Follow-up">
                            </div>
                            
                            <!-- Event Trigger Dropdown with Grouping -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Trigger Event</label>
                                <select name="event_trigger" x-model="selectedEvent" @change="updateVariables()" required class="block w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select an Event...</option>
                                    @php
                                        $grouped = collect($events)->groupBy('group');
                                    @endphp
                                    @foreach($grouped as $group => $eventList)
                                        <optgroup label="{{ $group }}">
                                            @foreach($eventList as $id => $meta)
                                                <option value="{{ $id }}">{{ $meta['label'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mapping Code</label>
                                <input type="text" name="code" x-model="code" required class="block w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" placeholder="estimate_followup">
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Subject</label>
                                <input type="text" name="subject" x-model="subject" required class="block w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ref: {{ '$estimate_number' }}">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase">HTML Content</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="insertBlock('card')" class="text-[10px] px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md font-bold border border-indigo-100">+ Info Card</button>
                                    <button type="button" @click="insertBlock('button')" class="text-[10px] px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md font-bold border border-indigo-100">+ Action Button</button>
                                    <button type="button" @click="insertBlock('table')" class="text-[10px] px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md font-bold border border-indigo-100">+ List Table</button>
                                </div>
                            </div>
                            <textarea name="body_html" x-model="body" rows="18" @input.debounce.500ms="updatePreview()" class="block w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono leading-relaxed bg-gray-50/30"></textarea>
                        </div>

                        <div class="pt-4 border-t border-gray-50 space-y-4">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Available Variables for <span x-text="events[selectedEvent]?.name || 'Selection'"></span></p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="(desc, v) in activeVariables" :key="v">
                                        <button type="button" @click="insertVar(v)" class="group relative inline-flex items-center px-2 py-1 bg-white border border-gray-200 text-[10px] font-mono text-indigo-600 rounded hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                                            <span x-text="'{{ $' + v + ' }}'"></span>
                                            <!-- Tooltip -->
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[9px] px-2 py-1 rounded whitespace-nowrap z-50" x-text="desc"></span>
                                        </button>
                                    </template>
                                    <div x-show="!selectedEvent" class="text-[10px] text-gray-400 italic">Select an event to see available variables...</div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end">
                                <button type="submit" class="bg-indigo-600 text-white px-8 py-2.5 rounded-full font-bold text-sm hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                                    {{ isset($emailTemplate) ? 'Update Template' : 'Create Template' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Preview -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-gray-500 uppercase">Live Preview</label>
                        <div x-show="loading" class="flex items-center text-xs text-indigo-600">
                             <svg class="animate-spin h-3 w-3 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                             Updating...
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-[650px] flex flex-col">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                             <div class="flex gap-1.5">
                                 <div class="w-3 h-3 rounded-full bg-red-400/80"></div>
                                 <div class="w-3 h-3 rounded-full bg-yellow-400/80"></div>
                                 <div class="w-3 h-3 rounded-full bg-green-400/80"></div>
                             </div>
                             <div class="ml-4 flex-1">
                                 <div class="bg-white border border-gray-200 rounded text-[11px] px-3 py-1.5 text-gray-600 font-medium truncate w-full shadow-inner" x-text="subject || 'Subject Preview...'"></div>
                             </div>
                        </div>
                        <iframe x-ref="previewFrame" class="w-full flex-1 border-0"></iframe>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function emailEditor() {
            return {
                name: @json($emailTemplate->name ?? ''),
                code: @json($emailTemplate->code ?? ''),
                selectedEvent: @json($emailTemplate->event_trigger ?? ''),
                subject: @json($emailTemplate->subject ?? ''),
                body: @json($emailTemplate->body_html ?? "@extends('emails.layout')\n\n@section('content')\n    <h1>Hello {{ \$notifiable_name }},</h1>\n    <p>We are writing to you regarding <strong>{{ \$estimate_number }}</strong>.</p>\n    <div class=\"card\">\n         <div class=\"card-title\">Summary</div>\n         <div class=\"card-row\">\n             <span class=\"card-label\">Amount</span>\n             <span class=\"card-value\">{{ \$total_amount }}</span>\n         </div>\n    </div>\n    <a href=\"{{ \$view_url }}\" class=\"btn\">View Details</a>\n@endsection"),
                events: @json($events),
                activeVariables: {},
                loading: false,
                
                init() {
                    this.updateVariables();
                    this.updatePreview();
                },

                updateVariables() {
                    if (this.selectedEvent && this.events[this.selectedEvent]) {
                        this.activeVariables = this.events[this.selectedEvent].variables;
                    } else {
                        this.activeVariables = {};
                    }
                },

                insertVar(v) {
                    const el = document.getElementsByName('body_html')[0];
                    const start = el.selectionStart;
                    const end = el.selectionEnd;
                    const varStr = '{{ $' + v + ' }}';
                    this.body = this.body.substring(0, start) + varStr + this.body.substring(end);
                    this.updatePreview();
                    
                    // Maintain focus and set cursor after variable
                    this.$nextTick(() => {
                        el.focus();
                        el.setSelectionRange(start + varStr.length, start + varStr.length);
                    });
                },

                insertBlock(type) {
                    let block = '';
                    if (type === 'card') {
                        block = `\n<div class="card">\n    <div class="card-title">Summary Details</div>\n    <div class="card-row">\n        <span class="card-label">Estimate #</span>\n        <span class="card-value">{{ $estimate_number }}</span>\n    </div>\n    <div class="card-row">\n        <span class="card-label">Total Amount</span>\n        <span class="card-value">{{ $total_amount }}</span>\n    </div>\n</div>\n`;
                    } else if (type === 'button') {
                        block = `\n<div class="text-center">\n    <a href="{{ $view_url }}" class="btn">View & Approve Online</a>\n</div>\n`;
                    } else if (type === 'table') {
                        block = `\n<div class="item-list">\n    <div class="item-row">\n        <span class="item-name">Premium Service Package</span>\n        <span class="item-desc">Comprehensive estimation and design brief.</span>\n    </div>\n</div>\n`;
                    }
                    
                    const el = document.getElementsByName('body_html')[0];
                    const start = el.selectionStart;
                    const end = el.selectionEnd;
                    this.body = this.body.substring(0, start) + block + this.body.substring(end);
                    this.updatePreview();
                },

                async updatePreview() {
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route('email-templates.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                body_html: this.body,
                                subject: this.subject
                            })
                        });
                        const data = await response.json();
                        const doc = this.$refs.previewFrame.contentWindow.document;
                        doc.open();
                        doc.write(data.html);
                        doc.close();
                    } catch (e) {
                        console.error('Preview failed', e);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>