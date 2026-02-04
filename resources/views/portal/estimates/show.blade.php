<x-portal-layout>
    <div class="max-w-4xl mx-auto" x-data="portalShow()">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200 animate-fade-in-down">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($estimate->status === 'accepted')
            <div class="mb-6 rounded-lg bg-emerald-50 p-6 border border-emerald-100 text-center shadow-sm">
                <p class="text-xl font-bold text-emerald-800 mb-1">🎉 Estimate Accepted</p>
                <p class="text-emerald-600 text-sm">Thank you for your business. We are excited to get started!</p>
            </div>
        @endif

        <!-- Main Invoice/Estimate Container -->
        <div
            class="bg-white shadow-xl rounded-2xl overflow-hidden mb-8 border border-slate-100 transition-all duration-300 hover:shadow-2xl">

            <!-- Header -->
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-6 sm:p-10 relative overflow-hidden">
                <!-- Decorative Circle -->
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-5 rounded-full blur-3xl">
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center relative z-10 gap-6">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Estimate
                            #{{ $estimate->estimate_number }}</div>
                        <h1 class="text-3xl sm:text-4xl font-black tracking-tight">{{ config('app.name') }}</h1>
                        <div class="mt-4 text-sm text-slate-300 leading-relaxed">
                            <p>Prepared for <span
                                    class="text-white font-bold">{{ $estimate->client->name ?? 'Valued Client' }}</span>
                            </p>
                            <p class="opacity-70">{{ $estimate->estimate_date->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                            <div class="text-xs text-slate-300 uppercase tracking-wider font-bold mb-1">Grand Total
                            </div>
                            <div class="text-2xl sm:text-3xl font-black">{{ $estimate->currency ?? 'INR' }}
                                {{ number_format($estimate->grand_total, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard / Graphs -->
            <div class="p-6 sm:p-8 bg-slate-50 border-b border-slate-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4">Cost Breakdown</h3>
                        <div class="h-48 relative">
                            <canvas id="costBreakdownChart"></canvas>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div
                            class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex justify-between items-center">
                            <div>
                                <div class="text-xs text-slate-500 font-bold uppercase">Subtotal</div>
                                <div class="text-lg font-bold text-slate-900">
                                    {{ number_format($estimate->subtotal, 2) }}</div>
                            </div>
                            <div class="h-8 w-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        @if($estimate->total_tax > 0)
                            <div
                                class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex justify-between items-center">
                                <div>
                                    <div class="text-xs text-slate-500 font-bold uppercase">Total Tax</div>
                                    <div class="text-lg font-bold text-slate-900">
                                        {{ number_format($estimate->total_tax, 2) }}</div>
                                </div>
                                <div
                                    class="h-8 w-8 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                    </svg>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Interactive Sections -->
            <div class="p-6 sm:p-8 space-y-4">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                    Scope of Work
                </h2>

                @foreach($estimate->sections as $section)
                    <div class="border border-slate-200 rounded-xl overflow-hidden transition-all duration-300"
                        :class="activeSection === {{ $section->id }} ? 'ring-2 ring-indigo-500/20 shadow-lg' : 'hover:shadow-md'">

                        <!-- Section Header -->
                        <button @click="toggleSection({{ $section->id }})"
                            class="w-full flex items-center justify-between p-4 bg-white hover:bg-slate-50 transition-colors cursor-pointer text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 transition-transform duration-300"
                                    :class="activeSection === {{ $section->id }} ? 'rotate-90' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900">{{ $section->name }}</h3>
                                    <p class="text-xs text-slate-500">{{ $section->items->count() }} Items</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-slate-900">{{ number_format($section->total, 2) }}</div>
                            </div>
                        </button>

                        <!-- Section Content (Items) -->
                        <div x-show="activeSection === {{ $section->id }}" x-collapse
                            class="bg-slate-50/50 border-t border-slate-100">
                            <div class="p-4 space-y-4">
                                @foreach($section->items as $item)
                                    <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex flex-row items-start gap-4 transition-all hover:shadow-md group/item">
                                        
                                        <!-- Image Column (Fixed Width) -->
                                        @php
                                            $imageUrl = $item->product ? $item->product->primary_image_url : null;
                                        @endphp
                                        @if(!$section->is_package && $imageUrl)
                                        <div class="w-16 h-16 sm:w-24 sm:h-24 bg-slate-50 rounded-lg shrink-0 overflow-hidden border border-slate-200 shadow-sm relative group-hover/item:shadow-md transition-all">
                                                <img src="{{ $imageUrl }}" class="w-full h-full object-cover transition-transform duration-500 group-hover/item:scale-110">
                                        </div>
                                        @endif

                                        <!-- Content Grid -->
                                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-y-3 sm:gap-4 min-w-0">
                                            
                                            <!-- Main Details (Name, Desc) -->
                                            <div class="sm:col-span-5">
                                                <h4 class="font-bold text-slate-900 text-sm mb-1 group-hover/item:text-indigo-600 transition-colors">{{ $item->name }}</h4>
                                                <div class="text-xs text-slate-500 leading-relaxed prose prose-sm max-w-none prose-p:my-0 prose-p:leading-relaxed">
                                                    {!! $item->description !!}
                                                </div>
                                                
                                                <!-- Unit Type Badge if exists -->
                                                 @if(!$section->is_package && $item->unit_type)
                                                    <span class="inline-flex mt-2 px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-500 rounded uppercase tracking-wide">{{ $item->unit_type }}</span>
                                                @endif
                                            </div>

                                            <!-- Configuration / Variants / Dims (Middle Col) -->
                                            <div class="sm:col-span-4 flex flex-col sm:justify-center space-y-2">
                                                <!-- Variants -->
                                                @if($item->options && is_array($item->options) && count($item->options) > 0)
                                                    <div class="flex flex-col gap-1.5 pl-0 sm:pl-3 sm:border-l-2 sm:border-slate-100">
                                                        @foreach($item->options as $key => $option)
                                                            @php
                                                                $label = $key;
                                                                $val = $option;
                                                                
                                                                // Handle structured options (e.g. [{"name": "Color", "value": "Blue"}])
                                                                if (is_array($option) || is_object($option)) {
                                                                    $opt = (array)$option;
                                                                    if (isset($opt['name']) && isset($opt['value'])) {
                                                                        $label = $opt['name'];
                                                                        $val = $opt['value'];
                                                                    } else {
                                                                        // Fallback if structure is unknown
                                                                        $val = json_encode($option);
                                                                    }
                                                                }
                                                            @endphp
                                                            <div class="flex flex-col leading-none">
                                                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">{{ str_replace('_', ' ', $label) }}</span>
                                                                <span class="text-xs font-semibold text-slate-700">{{ $val }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Dimensions -->
                                                @if(!$section->is_package && ($item->length || $item->width || $item->height))
                                                    <div class="flex flex-wrap gap-1.5 pl-0 sm:pl-3 sm:border-l-2 sm:border-slate-100 {{ ($item->options && count($item->options) > 0) ? '-mt-1' : '' }}">
                                                        @if($item->length)<div class="px-1.5 py-0.5 border border-slate-200 rounded text-[10px] text-slate-500 bg-slate-50"><span class="font-bold text-slate-700">L:</span> {{ $item->length + 0 }}</div>@endif
                                                        @if($item->width)<div class="px-1.5 py-0.5 border border-slate-200 rounded text-[10px] text-slate-500 bg-slate-50"><span class="font-bold text-slate-700">W:</span> {{ $item->width + 0 }}</div>@endif
                                                        @if($item->height)<div class="px-1.5 py-0.5 border border-slate-200 rounded text-[10px] text-slate-500 bg-slate-50"><span class="font-bold text-slate-700">H:</span> {{ $item->height + 0 }}</div>@endif
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Price (Right Col) -->
                                            <div class="sm:col-span-3 flex flex-row sm:flex-col justify-between sm:justify-center sm:items-end items-center mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-0 border-slate-50">
                                                <!-- Mobile Label -->
                                                <div class="sm:hidden text-xs text-slate-400 font-bold uppercase">Total</div>
                                                
                                                <div class="text-right">
                                                    <div class="text-sm font-black text-slate-900">{{ number_format($item->total, 2) }}</div>
                                                    <div class="text-xs text-slate-400 font-medium mt-0.5">{{ $item->quantity }} x {{ number_format($item->unit_price, 2) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Terms -->
            @if($estimate->terms)
                <div class="p-6 sm:p-8 border-t border-slate-100 bg-slate-50">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Terms & Conditions</h3>
                    <div class="prose prose-sm prose-slate max-w-none text-slate-600">
                        {!! nl2br(e($estimate->terms)) !!}
                    </div>
                </div>
            @endif

        </div>

        <!-- Sticky Mobile Actions -->
        @if(!in_array($estimate->status, ['accepted', 'declined']))
            <div
                class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] flex gap-3 z-30 sm:hidden">
                <button @click="showDeclineModal = true"
                    class="flex-1 py-3 text-red-600 font-bold text-sm bg-red-50 rounded-xl">Decline</button>
                <button @click="showAcceptModal = true"
                    class="flex-[2] py-3 bg-slate-900 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-500/30">Accept
                    Estimate</button>
            </div>
        @endif

        <!-- Desktop Actions -->
        @if(!in_array($estimate->status, ['accepted', 'declined']))
            <div class="hidden sm:flex justify-end gap-3 mb-10">
                <a href="{{ URL::signedRoute('portal.download', $estimate) }}"
                    class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg font-semibold hover:bg-slate-50 transition-colors">Download
                    PDF</a>
                <button @click="showDeclineModal = true"
                    class="px-4 py-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg font-semibold transition-colors">Decline</button>
                <button @click="showAcceptModal = true"
                    class="px-6 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold shadow-lg transition-all transform hover:-translate-y-0.5">Accept
                    Estimate</button>
            </div>
        @endif

        <!-- Modals -->
        <!-- Decline Modal -->
        <div x-show="showDeclineModal" class="relative z-50" style="display: none;">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-transition.opacity></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-xl transition-all sm:w-full sm:max-w-lg"
                        @click.away="showDeclineModal = false">
                        <form action="{{ URL::signedRoute('portal.decline', $estimate) }}" method="POST">
                            @csrf
                            <h3 class="text-lg font-bold text-slate-900">Decline Estimate</h3>
                            <p class="text-sm text-slate-500 mt-2 mb-4">Please tell us why you are declining so we can
                                improve.</p>
                            <textarea name="client_notes" rows="3"
                                class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500"
                                required></textarea>
                            <div class="mt-6 flex gap-3">
                                <button type="button" @click="showDeclineModal = false"
                                    class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-semibold rounded-lg">Cancel</button>
                                <button type="submit"
                                    class="flex-1 py-2.5 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700">Decline</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accept Modal -->
        <div x-show="showAcceptModal" class="relative z-50" style="display: none;">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-transition.opacity></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-xl transition-all sm:w-full sm:max-w-lg"
                        @click.away="showAcceptModal = false">
                        <form action="{{ URL::signedRoute('portal.accept', $estimate) }}" method="POST"
                            @submit.prevent="if(signaturePad.isEmpty()){ alert('Please provide a signature.'); return false; } document.getElementById('signature_input').value = signaturePad.toDataURL(); $el.submit();">
                            @csrf
                            <input type="hidden" name="signature" id="signature_input">
                            <h3 class="text-lg font-bold text-slate-900">Accept Estimate</h3>
                            <p class="text-sm text-slate-500 mt-2 mb-4">Sign below to confirm your acceptance.</p>

                            <div
                                class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 touch-none overflow-hidden relative">
                                <canvas id="signature-pad" class="w-full h-48 cursor-crosshair"></canvas>
                                <div class="absolute bottom-2 right-2 text-[10px] text-slate-400 pointer-events-none">
                                    Sign Here</div>
                            </div>
                            <div class="flex justify-end mt-2">
                                <button type="button" @click="signaturePad.clear()"
                                    class="text-xs text-red-500 font-medium hover:underline">Clear Signature</button>
                            </div>

                            <div class="mt-6 flex gap-3">
                                <button type="button" @click="showAcceptModal = false"
                                    class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-semibold rounded-lg">Cancel</button>
                                <button type="submit"
                                    class="flex-1 py-2.5 bg-slate-900 text-white font-semibold rounded-lg hover:bg-slate-800 shadow-lg">Confirm
                                    & Sign</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Call FAB (Mobile Only) -->
        <div class="fixed bottom-24 right-4 z-40 sm:hidden">
            <form action="{{ URL::signedRoute('portal.request-call', $estimate) }}" method="POST"
                onsubmit="return confirm('Request a call?');">
                @csrf
                <button type="submit" class="bg-indigo-600 text-white p-4 rounded-full shadow-xl shadow-indigo-500/40">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                        </path>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Signature Pad Lib -->
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('portalShow', () => ({
                    showCommentModal: false,
                    showDeclineModal: false,
                    showAcceptModal: false,
                    signaturePad: null,
                    activeSection: null, // For accordion
                    labels: @json($estimate->sections->pluck('name')),
                    totals: @json($estimate->sections->pluck('total')),

                    init() {
                        // Initialize Signature Pad
                        this.$watch('showAcceptModal', value => {
                            if (value) {
                                this.$nextTick(() => {
                                    var canvas = document.getElementById('signature-pad');
                                    if (canvas && typeof SignaturePad !== 'undefined') {
                                        this.signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
                                        window.addEventListener('resize', () => this.resizeCanvas(canvas));
                                        this.resizeCanvas(canvas);
                                    }
                                });
                            }
                        });

                        // Initialize Charts
                        this.initCharts();
                    },

                    resizeCanvas(canvas) {
                        if (!this.signaturePad) return;
                        var ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        canvas.getContext('2d').scale(ratio, ratio);
                        this.signaturePad.clear();
                    },

                    initCharts() {
                        const ctx = document.getElementById('costBreakdownChart');
                        if (ctx) {
                            new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: this.labels,
                                    datasets: [{
                                        data: this.totals,
                                        backgroundColor: [
                                            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1'
                                        ],
                                        borderWidth: 0
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'right',
                                            labels: {
                                                usePointStyle: true,
                                                font: { family: 'Inter', size: 11 }
                                            }
                                        }
                                    },
                                    cutout: '70%',
                                }
                            });
                        }
                    },

                    toggleSection(id) {
                        this.activeSection = this.activeSection === id ? null : id;
                    }
                }));
            });
        </script>
    </div>
</x-portal-layout>