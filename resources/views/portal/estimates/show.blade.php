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
            <div
                class="mb-6 rounded-lg bg-emerald-50 p-6 border border-emerald-100 text-center shadow-sm">
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
                    @if($estimate->status === 'accepted')
                        <!-- Corner Seal of Approval -->
                        <div class="absolute -top-12 -right-12 sm:-top-16 sm:-right-16 w-56 h-56 sm:w-72 sm:h-72 opacity-30 sm:opacity-50 pointer-events-none rotate-[15deg] select-none z-0">
                            <svg viewBox="0 0 200 200" class="w-full h-full text-emerald-500 fill-current">
                                <circle cx="100" cy="100" r="95" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="8,4" />
                                <circle cx="100" cy="100" r="85" fill="none" stroke="currentColor" stroke-width="6" />
                                <circle cx="100" cy="100" r="75" fill="none" stroke="currentColor" stroke-width="2" />
                                <path id="curve" d="M 40,100 A 60,60 0 1,1 160,100" fill="none" />
                                <text class="text-[14px] font-black uppercase tracking-[0.3em]">
                                    <textPath href="#curve" startOffset="50%" text-anchor="middle">APPROVED</textPath>
                                </text>
                                <rect x="15" y="85" width="170" height="30" rx="4" fill="currentColor" />
                                <text x="100" y="106" text-anchor="middle" class="text-white text-[20px] font-black uppercase tracking-widest">APPROVED</text>
                                <path d="M 40,100 A 60,60 0 1,0 160,100" fill="none" id="curve2" />
                                <text class="text-[12px] font-bold uppercase tracking-widest">
                                    <textPath href="#curve2" startOffset="50%" text-anchor="middle">{{ $estimate->signed_at ? $estimate->signed_at->format('Y-M-d') : '' }}</textPath>
                                </text>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">Estimate
                            #{{ $estimate->estimate_number }}</div>
                        
                        @php
                            $logo = \App\Models\Setting::getCached('app_logo');
                            $siteName = \App\Models\Setting::getCached('site_name', config('app.name'));
                        @endphp

                        @if($logo)
                            <img src="{{ \Illuminate\Support\Str::startsWith($logo, ['http://', 'https://']) ? $logo : asset($logo) }}" alt="{{ $siteName }}" class="h-12 w-auto object-contain relative z-10">
                        @else
                            <h1 class="text-3xl sm:text-4xl font-black tracking-tight relative z-10">{{ $siteName }}</h1>
                        @endif

                        <div class="mt-4 text-sm text-slate-300 leading-relaxed">
                            <p>Prepared for <span
                                    class="text-white font-bold">{{ $estimate->client->name ?? 'Valued Client' }}</span>
                            </p>
                            <p class="opacity-70">{{ optional($estimate->estimate_date)->format('M d, Y') ?? 'N/A' }}</p>
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

            <!-- Company Intro & Showcase -->
            <div class="p-6 sm:p-8 pb-0 space-y-8">
                <!-- Welcome / Intro Card -->
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100 flex flex-col md:flex-row gap-8 items-start">
                    <div class="flex-1 space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                             <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wide">
                                 {{ \App\Models\Setting::getCached('portal_company_badge', 'Welcome') }}
                             </span>
                             <span class="h-px flex-1 bg-slate-100"></span>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 leading-tight">
                            {{ \App\Models\Setting::getCached('portal_company_title', 'Crafting Your Dream Space') }}
                        </h2>
                        <div class="prose prose-sm prose-slate text-slate-600 text-justify">
                            @php
                                $companyIntro = \App\Models\Setting::getCached('portal_company_intro');
                                
                                // Video Logic
                                $uploadedVideo = \App\Models\Setting::getCached('portal_company_video');
                                $directVideoUrl = \App\Models\Setting::getCached('portal_company_video_url');
                                $youtubeUrl = \App\Models\Setting::getCached('portal_company_youtube_url');
                                
                                $isYoutube = false;
                                $videoUrl = null;
                                
                                if ($uploadedVideo) {
                                    $videoUrl = $uploadedVideo;
                                } elseif ($directVideoUrl) {
                                    $videoUrl = $directVideoUrl;
                                } elseif ($youtubeUrl) {
                                    $isYoutube = true;
                                    // Extract YouTube ID
                                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtubeUrl, $match);
                                    $youtubeId = $match[1] ?? null;
                                    $videoUrl = $youtubeId ? "https://www.youtube.com/embed/{$youtubeId}?autoplay=1" : null;
                                }
                                
                                // Default if nothing is set
                                if (!$videoUrl) {
                                    $videoUrl = 'https://archive.org/download/BigBuckBunny_124/Content/big_buck_bunny_720p_surround.mp4';
                                }
                                
                                $videoThumbnail = \App\Models\Setting::getCached('portal_company_video_thumbnail', 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=1200');
                                $showcaseImages = json_decode(\App\Models\Setting::getCached('portal_company_showcase_images', '[]'), true) ?: [
                                    'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&q=80&w=1200',
                                    'https://images.unsplash.com/photo-1616486338812-3dadae4b4f9d?auto=format&fit=crop&q=80&w=1200',
                                    'https://images.unsplash.com/photo-1616137466211-f939a420be84?auto=format&fit=crop&q=80&w=1200',
                                    'https://images.unsplash.com/photo-1616137254415-88573fc0399d?auto=format&fit=crop&q=80&w=1200',
                                    'https://images.unsplash.com/photo-1617104424032-b9bd6972d0e4?auto=format&fit=crop&q=80&w=1200'
                                ];
                            @endphp
                            @if($companyIntro)
                                {!! nl2br(e($companyIntro)) !!}
                            @elseif($estimate->client_note)
                                {!! nl2br(e($estimate->client_note)) !!}
                            @else
                                <p>Thank you for considering us for your project. At <strong>{{ config('app.name', 'Our Company') }}</strong>, we believe in transforming spaces into living experiences. This estimate outlines our proposed scope of work, carefully curated to meet your unique needs and style. We look forward to the possibility of working together.</p>
                            @endif
                        </div>
                        
                        <!-- Video Introduction (Popup trigger) -->
                        <div @click="showVideoModal = true" class="mt-6 rounded-xl overflow-hidden shadow-lg border border-slate-200 relative group cursor-pointer aspect-video bg-slate-900 group">
                            <img src="{{ Str::startsWith($videoThumbnail, 'http') ? $videoThumbnail : asset($videoThumbnail) }}" class="w-full h-full object-cover opacity-60 group-hover:opacity-40 transition-opacity">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center pl-1 shadow-sm">
                                        <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Gallery (Grid) -->
                    <div class="w-full md:w-1/3 flex flex-col gap-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                            {{ \App\Models\Setting::getCached('portal_company_gallery_title', 'Recent Projects') }}
                        </h3>
                        <div class="grid grid-cols-2 gap-2">
                             <template x-for="(img, index) in projectImages.slice(0, 3)" :key="index">
                                <div @click="currentImageIndex = index; showImageModal = true" class="aspect-square rounded-lg bg-slate-100 overflow-hidden relative group cursor-pointer">
                                    <img :src="img.includes('unsplash') ? img.replace('w=1200', 'w=400') : (img.startsWith('http') ? img : '{{ asset('') }}' + img.replace(/^\//, ''))" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                    </div>
                                </div>
                             </template>
                             <template x-if="projectImages.length > 3">
                             <div @click="currentImageIndex = 3; showImageModal = true" class="aspect-square rounded-lg bg-slate-900 overflow-hidden relative group flex flex-col items-center justify-center cursor-pointer hover:bg-slate-800 transition-colors">
                                <span class="text-white text-sm font-black" x-text="'+' + (projectImages.length - 3)"></span>
                                <span class="text-white/50 text-[10px] font-bold uppercase tracking-tighter">View All</span>
                             </div>
                             </template>
                        </div>
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
                                                                    $opt = (array) $option;
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

            <!-- Terms & Conditions Button -->
            @if($estimate->terms)
                <div class="p-6 sm:p-8 flex justify-center border-t border-slate-100">
                    <button @click="showTermsModal = true" class="group flex items-center gap-3 px-6 py-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-200 transition-all">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div class="text-left">
                            <div class="text-sm font-bold text-slate-900 leading-tight">Terms & Conditions</div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Click to view full document</div>
                        </div>
                        <svg class="w-5 h-5 text-slate-300 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
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

            <!-- Desktop Actions -->
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
        @elseif($estimate->status === 'accepted')
            <!-- Execution Details Card -->
            <div class="mb-12 bg-white rounded-[2.5rem] p-8 sm:p-12 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 animate-fade-in relative overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Left Column: Authenticity Seal -->
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="relative mb-6">
                            <svg viewBox="0 0 200 200" class="w-48 h-48 sm:w-64 sm:h-64 text-emerald-500 fill-current drop-shadow-xl filter drop-shadow-[0_4px_10px_rgba(16,185,129,0.2)]">
                                <filter id="grunge-v2">
                                    <feTurbulence type="fractalNoise" baseFrequency="0.5" numOctaves="3" result="noise" />
                                    <feDisplacementMap in="SourceGraphic" in2="noise" scale="3" />
                                </filter>
                                <g filter="url(#grunge-v2)">
                                    <circle cx="100" cy="100" r="92" fill="none" stroke="currentColor" stroke-width="3" />
                                    <circle cx="100" cy="100" r="82" fill="none" stroke="currentColor" stroke-width="8" />
                                    <circle cx="100" cy="100" r="72" fill="none" stroke="currentColor" stroke-width="2" />
                                    <path id="circlePath-v2" d="M 40,100 A 60,60 0 1,1 160,100" fill="none" />
                                    <text class="text-[14px] font-black uppercase tracking-[0.2em]">
                                        <textPath href="#circlePath-v2" startOffset="50%" text-anchor="middle">APPROVED</textPath>
                                    </text>
                                    <rect x="15" y="82" width="170" height="36" rx="4" fill="currentColor" />
                                    <text x="100" y="108" text-anchor="middle" class="text-white text-[22px] font-black uppercase tracking-[0.1em]">APPROVED</text>
                                    <path d="M 40,100 A 60,60 0 1,0 160,100" fill="none" id="datePath-v2" />
                                    <text class="text-[11px] font-bold uppercase tracking-[0.3em]">
                                        <textPath href="#datePath-v2" startOffset="50%" text-anchor="middle" dominant-baseline="hanging">{{ $estimate->signed_at ? $estimate->signed_at->format('M d, Y') : '' }}</textPath>
                                    </text>
                                </g>
                            </svg>
                        </div>
                        <div class="inline-flex items-center px-4 py-1.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-black uppercase tracking-widest border border-emerald-200">
                            Verified Record
                        </div>
                    </div>

                    <!-- Right Column: Execution Information -->
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 uppercase tracking-widest border-b-2 border-slate-900 inline-block pb-1 mb-6">Approval Details</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Client Signature</p>
                                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 relative group overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-br from-white/50 to-transparent pointer-events-none"></div>
                                        @if($estimate->signature)
                                            <img src="{{ $estimate->signature }}" alt="Client Signature" class="max-h-20 w-auto grayscale contrast-125 mix-blend-multiply mx-auto transition-transform group-hover:scale-105 duration-500">
                                            <div class="absolute bottom-2 right-4 text-[8px] font-mono text-slate-300">Verified ID: #{{ str_pad($estimate->id, 8, '0', STR_PAD_LEFT) }}</div>
                                        @else
                                            <div class="h-20 flex items-center justify-center text-slate-300 italic text-sm text-[11px] uppercase font-bold tracking-widest">Digital Signature Missing</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Date Signed</p>
                                        <p class="text-sm font-bold text-slate-900 leading-tight">{{ $estimate->signed_at ? $estimate->signed_at->format('H:i T') : 'N/A' }}</p>
                                    </div>
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">IP Address</p>
                                        <p class="text-sm font-bold text-slate-900 leading-tight">{{ $estimate->signer_ip ?? '127.0.0.1' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-4">
                            <a href="{{ URL::signedRoute('portal.download', $estimate) }}" 
                               class="flex items-center justify-center gap-3 w-full py-4 bg-slate-900 text-white rounded-2xl font-bold transition-all hover:bg-slate-800 hover:shadow-xl group">
                                <svg class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <span>Download Final PDF with Seal</span>
                            </a>
                            <p class="text-center text-[10px] text-slate-400 mt-4 font-medium italic">A copy of this execution has been emailed to both parties.</p>
                        </div>
                    </div>
                </div>
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

                            <div class="mt-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" x-model="agreedToTerms" class="sr-only">
                                        <div class="w-10 h-6 rounded-full transition-colors group-hover:bg-slate-300" 
                                            :class="agreedToTerms ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.3)]' : 'bg-slate-200'"></div>
                                        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-all shadow-sm" 
                                            :class="agreedToTerms ? 'translate-x-4' : 'translate-x-0'"></div>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-[0.15em] select-none">I agree to the <button type="button" @click="showTermsModal = true" class="text-indigo-600 hover:underline">Terms & Conditions</button></span>
                                </label>
                            </div>

                            <div class="mt-6 flex gap-3">
                                <button type="button" @click="showAcceptModal = false"
                                    class="flex-1 py-3 bg-slate-100 text-slate-700 font-bold rounded-2xl transition-all hover:bg-slate-200">Cancel</button>
                                <button type="submit"
                                    class="flex-1 py-3 bg-slate-900 text-white font-bold rounded-2xl shadow-lg transition-all hover:bg-slate-800 disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed"
                                    :disabled="!agreedToTerms">Confirm & Sign</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Modal -->
        <div x-show="showVideoModal" class="relative z-[60]" style="display: none;" @keydown.escape.window="showVideoModal = false">
            <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-xl transition-opacity" x-transition.opacity @click="showVideoModal = false"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-5xl aspect-video bg-black rounded-3xl overflow-hidden shadow-[0_0_100px_rgba(99,102,241,0.2)] border border-white/10" @click.stop>
                        <button @click="showVideoModal = false" class="absolute top-6 right-6 z-20 w-12 h-12 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-md transition-all border border-white/20 hover:scale-110">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        @if($isYoutube)
                            <iframe id="youtube-player" class="w-full h-full" src="{{ $videoUrl }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        @else
                            <video x-ref="companyVideo" class="w-full h-full" controls>
                                <source src="{{ Str::startsWith($videoUrl, 'http') ? $videoUrl : asset($videoUrl) }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Gallery Lightbox -->
        <div x-show="showImageModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/95 backdrop-blur-xl" 
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @keydown.window.escape="showImageModal = false"
            @keydown.window.left="prevImage()"
            @keydown.window.right="nextImage()"
            style="display: none;">
            
            <!-- Close Button -->
            <button @click="showImageModal = false" class="absolute top-6 right-6 text-white/50 hover:text-white z-10 p-2 bg-white/10 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <!-- Navigation Arrows -->
            <button @click="prevImage()" class="absolute left-6 top-1/2 -translate-y-1/2 p-4 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button @click="nextImage()" class="absolute right-6 top-1/2 -translate-y-1/2 p-4 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>

            <!-- Image Container -->
            <div class="relative max-w-[90vw] max-h-[85vh] flex flex-col items-center gap-6" @click.away="showImageModal = false">
                <img :src="projectImages[currentImageIndex].startsWith('http') ? projectImages[currentImageIndex] : '{{ asset('') }}' + projectImages[currentImageIndex].replace(/^\//, '')" 
                    class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl transition-all duration-500 transform animate-scale-in">
                
                <!-- Counter & Title -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-2 rounded-full border border-white/10 flex items-center gap-4">
                    <span class="text-white text-xs font-bold uppercase tracking-widest whitespace-nowrap">Project Image <span x-text="currentImageIndex + 1"></span> / <span x-text="projectImages.length"></span></span>
                </div>
            </div>
        </div>

        <!-- Terms Modal -->
        <div x-show="showTermsModal" class="relative z-[70]" style="display: none;" @keydown.escape.window="showTermsModal = false">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" x-transition.opacity @click="showTermsModal = false"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-3xl bg-white/95 backdrop-blur-xl rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-white/20 flex flex-col max-h-[85vh] transition-all transform animate-scale-in" @click.stop>
                        <!-- Premium Header -->
                        <div class="p-8 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white/50 backdrop-blur-md z-10 rounded-t-[2.5rem]">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center text-white shadow-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-widest leading-none">Terms & Conditions</h3>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Rules & Agreement</p>
                                </div>
                            </div>
                            <button @click="showTermsModal = false" class="w-10 h-10 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center hover:bg-slate-200 transition-colors focus:ring-2 focus:ring-slate-200 outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- Content Area -->
                        <div class="p-10 overflow-y-auto">
                            <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed text-sm prose-p:mb-4 prose-headings:text-slate-900 prose-headings:font-black prose-headings:uppercase prose-headings:tracking-tighter prose-strong:text-slate-900 prose-strong:font-black">
                                {!! $estimate->terms !!}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-8 border-t border-slate-100 flex justify-end bg-slate-50/50 rounded-b-[2.5rem] items-center gap-4">
                             <p class="hidden sm:block text-[9px] font-black text-slate-400 uppercase tracking-widest italic">Scroll to read the complete document</p>
                            <button @click="showTermsModal = false" class="px-8 py-3 bg-slate-900 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 active:translate-y-0">Close</button>
                        </div>
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
                    showVideoModal: false,
                    showTermsModal: false,
                    showImageModal: false,
                    agreedToTerms: false,
                    selectedImage: null,
                    currentImageIndex: 0,
                    projectImages: @json($showcaseImages),
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

                        // Watch for video modal open/close to play/pause video
                        this.$watch('showVideoModal', value => {
                            if (value) {
                                // Playing logic
                                if (this.$refs.companyVideo) {
                                    this.$nextTick(() => { this.$refs.companyVideo.play(); });
                                }
                                // For YouTube, we might need to reset the src to start playing if it was cleared
                                const yt = document.getElementById('youtube-player');
                                if (yt && !yt.src) {
                                    yt.src = '{{ $videoUrl }}';
                                }
                            } else {
                                // Pause local video
                                if (this.$refs.companyVideo) {
                                    this.$refs.companyVideo.pause();
                                }
                                // Stop YouTube video by clearing/resetting src
                                const yt = document.getElementById('youtube-player');
                                if (yt) {
                                    const currentSrc = yt.src;
                                    yt.src = ''; // Clear src to stop video
                                    // Special case: if we want it to stay loaded but paused, we'd need YT API, 
                                    // but clearing src is the most reliable 'hard stop'.
                                }
                            }
                        });

                        // Watch for image modal close
                        this.$watch('showImageModal', value => {
                            if (value) {
                                document.body.style.overflow = 'hidden';
                            } else {
                                document.body.style.overflow = '';
                            }
                        });

                        // Initialize Charts
                        this.initCharts();
                    },

                    nextImage() {
                        this.currentImageIndex = (this.currentImageIndex + 1) % this.projectImages.length;
                    },

                    prevImage() {
                        this.currentImageIndex = (this.currentImageIndex - 1 + this.projectImages.length) % this.projectImages.length;
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