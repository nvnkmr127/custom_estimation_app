<x-portal-layout>
    <style>
        @keyframes firework {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0);
            }

            30% {
                transform: scale(1.3);
                box-shadow: 0 0 0 20px rgba(79, 70, 229, 0.2);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 40px rgba(79, 70, 229, 0);
            }
        }

        .firework-animation {
            animation: firework 1s ease-out infinite;
        }

        .step-transition {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .roadmap-line-fill {
            transition: width 1s cubic-bezier(0.65, 0, 0.35, 1);
        }
    </style>

    <div class="max-w-4xl mx-auto pb-24 sm:pb-8" x-data="portalShow()" wire:ignore>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        @auth
            @if(auth()->user()->isAdmin() || auth()->user()->hasRole(['sales_manager', 'sales']))
                <!-- Internal Preview Mode Indicator -->
                <div class="mb-6 bg-indigo-600 rounded-2xl p-4 shadow-lg shadow-indigo-500/20 border border-indigo-500 flex items-center justify-between overflow-hidden relative group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-5 rounded-full blur-2xl group-hover:opacity-10 transition-opacity"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white backdrop-blur-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <div class="text-[10px] font-black text-indigo-200 uppercase tracking-[0.2em]">Staff View Only</div>
                                <span class="w-1 h-1 rounded-full bg-indigo-300"></span>
                                <div class="text-[10px] font-black text-indigo-100 uppercase tracking-[0.2em]">
                                    Status: {{ strtoupper($estimate->client_status ?: $estimate->estimate_status) }}
                                    @if($estimate->approval_status && $estimate->approval_status !== 'not_required')
                                        ({{ strtoupper($estimate->approval_status) }})
                                    @endif
                                </div>
                            </div>
                            <h3 class="text-white font-black tracking-tight leading-none">Internal Preview Mode</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 relative z-10">
                        <a href="{{ route('portal.preview-list') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-[10px] font-black uppercase tracking-widest backdrop-blur-md transition-all border border-white/10">
                            Master List
                        </a>
                        <a href="{{ route('estimates.edit', $estimate) }}" class="px-4 py-2 bg-white text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 transition-all shadow-sm">
                            Edit Estimate
                        </a>
                    </div>
                </div>
            @endif
        @endauth

        <title>Estimate Preview | {{ $estimate->estimate_number }}</title>
        <meta name="description" content="View and accept your custom estimate from {{ config('app.name') }}. Professional estimate #{{ $estimate->estimate_number }} for {{ $estimate->client_name }}.">
    
        <!-- External Libs -->
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

        @if($estimate->client_status === 'accepted' || $estimate->estimate_status === 'accepted')
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
                    @if($estimate->client_status === 'accepted' || $estimate->estimate_status === 'accepted')
                        <!-- Corner Seal of Approval -->
                        <div
                            class="absolute -top-12 -right-12 sm:-top-16 sm:-right-16 w-56 h-56 sm:w-72 sm:h-72 opacity-30 sm:opacity-50 pointer-events-none rotate-[15deg] select-none z-0">
                            <svg viewBox="0 0 200 200" class="w-full h-full text-emerald-500 fill-current">
                                <circle cx="100" cy="100" r="95" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-dasharray="8,4" />
                                <circle cx="100" cy="100" r="85" fill="none" stroke="currentColor" stroke-width="6" />
                                <circle cx="100" cy="100" r="75" fill="none" stroke="currentColor" stroke-width="2" />
                                <path id="curve" d="M 40,100 A 60,60 0 1,1 160,100" fill="none" />
                                <text class="text-[14px] font-black uppercase tracking-[0.3em]">
                                    <textPath href="#curve" startOffset="50%" text-anchor="middle">APPROVED</textPath>
                                </text>
                                <rect x="15" y="85" width="170" height="30" rx="4" fill="currentColor" />
                                <text x="100" y="106" text-anchor="middle"
                                    class="text-white text-[20px] font-black uppercase tracking-widest">APPROVED</text>
                                <path d="M 40,100 A 60,60 0 1,0 160,100" fill="none" id="curve2" />
                                <text class="text-[12px] font-bold uppercase tracking-widest">
                                    <textPath href="#curve2" startOffset="50%" text-anchor="middle">
                                        {{ $estimate->signed_at ? $estimate->signed_at->format('Y-M-d') : '' }}
                                    </textPath>
                                </text>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">
                            Estimate
                            #{{ $estimate->estimate_number }}</div>

                        @php
                            $logo = \App\Models\Setting::getCached('app_logo');
                            $siteName = \App\Models\Setting::getCached('site_name', config('app.name'));
                        @endphp

                        @if($logo)
                            <img src="{{ \Illuminate\Support\Str::startsWith($logo, ['http://', 'https://']) ? $logo : asset($logo) }}"
                                alt="{{ $siteName }}" class="h-12 w-auto object-contain relative z-10">
                        @else
                            <h1 class="text-3xl sm:text-4xl font-black tracking-tight relative z-10">{{ $siteName }}</h1>
                        @endif

                        <div class="mt-4 text-sm text-slate-300 leading-relaxed">
                            <p>Prepared for <span
                                    class="text-white font-bold">{{ $estimate->client->name ?? 'Valued Client' }}</span>
                            </p>
                            <p class="opacity-70">{{ optional($estimate->estimate_date)->format('M d, Y') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="w-full sm:w-auto text-left sm:text-right">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                            <div class="text-xs text-slate-300 uppercase tracking-wider font-bold mb-1">Grand Total
                            </div>
                            <div class="text-2xl sm:text-3xl font-black">{{ $estimate->currency_symbol }}
                                {{ number_format($estimate->grand_total, 2) }}
                            </div>

                            @if($estimate->discount_total > 0)
                                <div
                                    class="inline-flex mt-2 items-center px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[9px] font-black uppercase tracking-widest border border-amber-500/30 animate-pulse-slow">
                                    <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z">
                                        </path>
                                    </svg>
                                    Exclusive Savings:
                                    {{ $estimate->currency_symbol }}{{ number_format($estimate->discount_total, 0) }}
                                </div>
                            @endif

                            @if($estimate->expiry_date)
                                <div class="mt-3 pt-3 border-t border-white/10">
                                    <div class="flex flex-col items-end">
                                        <div class="text-[10px] text-slate-400 uppercase tracking-[0.2em] font-bold mb-1">
                                            Est. Expiry</div>
                                        <div class="text-xs font-bold text-rose-400 mb-2">
                                            {{ $estimate->expiry_date->format('M d, Y') }}
                                        </div>

                                        <div class="flex gap-1.5" x-show="!countdown.expired">
                                            <div class="flex flex-col items-center">
                                                <div class="bg-white/10 rounded px-1 min-w-[20px] text-center"><span
                                                        class="text-[10px] font-bold" x-text="countdown.days">0</span></div>
                                                <span
                                                    class="text-[8px] uppercase tracking-tighter opacity-50 font-bold mt-0.5">Days</span>
                                            </div>
                                            <div class="text-[10px] font-bold opacity-30 mt-0.5">:</div>
                                            <div class="flex flex-col items-center">
                                                <div class="bg-white/10 rounded px-1 min-w-[20px] text-center"><span
                                                        class="text-[10px] font-bold" x-text="countdown.hours">0</span>
                                                </div>
                                                <span
                                                    class="text-[8px] uppercase tracking-tighter opacity-50 font-bold mt-0.5">Hrs</span>
                                            </div>
                                            <div class="text-[10px] font-bold opacity-30 mt-0.5">:</div>
                                            <div class="flex flex-col items-center">
                                                <div class="bg-white/10 rounded px-1 min-w-[20px] text-center"><span
                                                        class="text-[10px] font-bold" x-text="countdown.minutes">0</span>
                                                </div>
                                                <span
                                                    class="text-[8px] uppercase tracking-tighter opacity-50 font-bold mt-0.5">Min</span>
                                            </div>
                                            <div class="text-[10px] font-bold opacity-30 mt-0.5">:</div>
                                            <div class="flex flex-col items-center">
                                                <div
                                                    class="bg-white/10 rounded px-1 min-w-[20px] text-center text-rose-400">
                                                    <span class="text-[10px] font-bold" x-text="countdown.seconds">0</span>
                                                </div>
                                                <span
                                                    class="text-[8px] uppercase tracking-tighter opacity-50 font-bold mt-0.5">Sec</span>
                                            </div>
                                        </div>
                                        <div x-show="countdown.expired"
                                            class="text-[10px] font-bold text-rose-500 uppercase tracking-widest bg-rose-500/10 px-2 py-0.5 rounded">
                                            Expired
                                        </div>
                                    </div>
                                </div>
                            @endif
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Card 1: Timeline (Part 2) -->
                        <div
                            class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-center">
                            <div class="flex items-center gap-2 mb-1">
                                <div
                                    class="h-6 w-6 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Est. Timeline
                                </div>
                            </div>
                            <div class="text-sm font-bold text-slate-900">
                                {{ \App\Models\Setting::getCached('portal_timeline', '6-8 Weeks') }}
                            </div>
                        </div>

                        <!-- Card 2: Quality (Part 2) -->
                        <div
                            class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-center">
                            <div class="flex items-center gap-2 mb-1">
                                <div
                                    class="h-6 w-6 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Quality
                                    Standard</div>
                            </div>
                            <div class="text-sm font-bold text-slate-900">
                                {{ \App\Models\Setting::getCached('portal_quality_badge', 'Premium Grade') }}
                            </div>
                        </div>

                        <!-- Card 3: Project Scale (Part 3) -->
                        <div
                            class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-center">
                            <div class="flex items-center gap-2 mb-1">
                                <div
                                    class="h-6 w-6 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                        </path>
                                    </svg>
                                </div>
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Project Scale
                                </div>
                            </div>
                            <div class="text-sm font-bold text-slate-900">
                                {{ $estimate->sections->count() }} Areas / {{ $estimate->items->count() }} Items
                            </div>
                        </div>

                        <!-- Card 4: Savings/Value (Part 3) -->
                        <div
                            class="p-4 rounded-xl shadow-sm border flex flex-col justify-center transition-all duration-500 {{ $estimate->discount_total > 0 ? 'bg-amber-50 border-amber-200 shadow-amber-100/50 ring-2 ring-amber-500/5' : 'bg-white border-slate-100' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <div
                                    class="h-6 w-6 rounded-full {{ $estimate->discount_total > 0 ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="text-[10px] {{ $estimate->discount_total > 0 ? 'text-amber-800' : 'text-slate-500' }} font-bold uppercase tracking-wider">
                                    Financial Value</div>
                            </div>
                            <div
                                class="text-sm font-bold {{ $estimate->discount_total > 0 ? 'text-amber-900' : 'text-slate-900' }}">
                                @if($estimate->discount_total > 0)
                                    {{ $estimate->currency_symbol }} {{ number_format($estimate->discount_total, 2) }}
                                    Savings Applied
                                @else
                                    Price Protection Lock
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Intro & Showcase -->
            <div class="p-6 sm:p-8 pb-0 space-y-8">
                <!-- Welcome / Intro Card -->
                <div
                    class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100 flex flex-col md:flex-row gap-8 items-start">
                    <div class="flex-1 space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wide">
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
                                <p>Thank you for considering us for your project. At
                                    <strong>{{ config('app.name', 'Our Company') }}</strong>, we believe in transforming
                                    spaces into living experiences. This estimate outlines our proposed scope of work,
                                    carefully curated to meet your unique needs and style. We look forward to the
                                    possibility of working together.
                                </p>
                            @endif
                        </div>

                        <!-- Video Introduction (Popup trigger) -->
                        <div @click="showVideoModal = true"
                            class="mt-6 rounded-xl overflow-hidden shadow-lg border border-slate-200 relative group cursor-pointer aspect-video bg-slate-900 group">
                            <img src="{{ Str::startsWith($videoThumbnail, 'http') ? $videoThumbnail : asset($videoThumbnail) }}"
                                class="w-full h-full object-cover opacity-60 group-hover:opacity-40 transition-opacity">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div
                                    class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <div
                                        class="w-10 h-10 bg-white rounded-full flex items-center justify-center pl-1 shadow-sm">
                                        <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
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
                                <div @click="openProjectGallery(index)"
                                    class="aspect-square rounded-lg bg-slate-100 overflow-hidden relative group cursor-pointer">
                                    <img :src="img.includes('unsplash') ? img.replace('w=1200', 'w=400') : (img.startsWith('http') ? img : '{{ asset('') }}' + img.replace(/^\//, ''))"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    <div
                                        class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                            </template>
                            <div x-show="projectImages.length > 3" @click="openProjectGallery(3)"
                                class="aspect-square rounded-lg bg-slate-900 overflow-hidden relative group flex flex-col items-center justify-center cursor-pointer hover:bg-slate-800 transition-colors">
                                <span class="text-white text-sm font-black"
                                    x-text="'+' + (projectImages.length - 3)"></span>
                                <span class="text-white/50 text-[10px] font-bold uppercase tracking-tighter">View
                                    All</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Roadmap / Journey -->
            <div class="px-6 sm:px-8 py-8 bg-white overflow-x-auto no-scrollbar">
                <div class="min-w-[600px]">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-8 text-center">Your
                        Project Journey (Preview)</h3>

                    <div class="relative flex justify-between">
                        <!-- Connecting Line -->
                        <div class="absolute top-5 left-10 right-10 h-0.5 bg-slate-100 z-0">
                            <div class="h-full bg-indigo-600 roadmap-line-fill"
                                :style="'width: ' + (roadmapStep * 25) + '%'"></div>
                        </div>

                        <!-- Step 1: Selection -->
                        <div class="relative z-10 flex flex-col items-center w-32">
                            <div class="w-10 h-10 rounded-full step-transition flex items-center justify-center shadow-lg"
                                :class="roadmapStep >= 0 ? 'bg-indigo-600 text-white ring-8 ring-indigo-50' : 'bg-white border-2 border-slate-100 text-slate-300'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span
                                class="mt-4 text-[10px] font-black transition-colors duration-500 uppercase tracking-widest text-center"
                                :class="roadmapStep === 0 ? 'text-indigo-600' : (roadmapStep > 0 ? 'text-slate-900' : 'text-slate-400')">Selection</span>
                            <span class="mt-1 text-[9px] font-bold text-center h-3"
                                :class="roadmapStep === 0 ? 'text-indigo-500' : 'text-transparent'"
                                x-text="roadmapStep === 0 ? 'Current Phase' : ''"></span>
                        </div>

                        <!-- Step 2: Design -->
                        <div class="relative z-10 flex flex-col items-center w-32">
                            <div class="w-10 h-10 rounded-full step-transition flex items-center justify-center transition-all duration-700 shadow-sm"
                                :class="roadmapStep >= 1 ? 'bg-indigo-600 text-white ring-8 ring-indigo-50 shadow-lg' : 'bg-white border-2 border-slate-100 text-slate-300'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                                    </path>
                                </svg>
                            </div>
                            <span
                                class="mt-4 text-[10px] font-black transition-colors duration-500 uppercase tracking-widest text-center"
                                :class="roadmapStep === 1 ? 'text-indigo-600' : (roadmapStep > 1 ? 'text-slate-900' : 'text-slate-400')">3D
                                Design</span>
                            <span class="mt-1 text-[9px] font-bold text-center h-3"
                                :class="roadmapStep === 1 ? 'text-indigo-500' : 'text-transparent'"
                                x-text="roadmapStep === 1 ? 'Active Phase' : ''"></span>
                        </div>

                        <!-- Step 3: Production -->
                        <div class="relative z-10 flex flex-col items-center w-32">
                            <div class="w-10 h-10 rounded-full step-transition flex items-center justify-center transition-all duration-700 shadow-sm"
                                :class="roadmapStep >= 2 ? 'bg-indigo-600 text-white ring-8 ring-indigo-50 shadow-lg' : 'bg-white border-2 border-slate-100 text-slate-300'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                            </div>
                            <span
                                class="mt-4 text-[10px] font-black transition-colors duration-500 uppercase tracking-widest text-center"
                                :class="roadmapStep === 2 ? 'text-indigo-600' : (roadmapStep > 2 ? 'text-slate-900' : 'text-slate-400')">Production</span>
                            <span class="mt-1 text-[9px] font-bold text-center h-3"
                                :class="roadmapStep === 2 ? 'text-indigo-500' : 'text-transparent'"
                                x-text="roadmapStep === 2 ? 'In Manufacturing' : ''"></span>
                        </div>

                        <!-- Step 4: Installation -->
                        <div class="relative z-10 flex flex-col items-center w-32">
                            <div class="w-10 h-10 rounded-full step-transition flex items-center justify-center transition-all duration-700 shadow-sm"
                                :class="roadmapStep >= 3 ? 'bg-indigo-600 text-white ring-8 ring-indigo-50 shadow-lg' : 'bg-white border-2 border-slate-100 text-slate-300'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z">
                                    </path>
                                </svg>
                            </div>
                            <span
                                class="mt-4 text-[10px] font-black transition-colors duration-500 uppercase tracking-widest text-center"
                                :class="roadmapStep === 3 ? 'text-indigo-600' : (roadmapStep > 3 ? 'text-slate-900' : 'text-slate-400')">Fitting</span>
                            <span class="mt-1 text-[9px] font-bold text-center h-3"
                                :class="roadmapStep === 3 ? 'text-indigo-500' : 'text-transparent'"
                                x-text="roadmapStep === 3 ? 'On-Site Execution' : ''"></span>
                        </div>

                        <!-- Step 5: Handover -->
                        <div class="relative z-10 flex flex-col items-center w-32">
                            <div class="w-10 h-10 rounded-full step-transition flex items-center justify-center transition-all duration-700 shadow-sm"
                                :class="roadmapStep === 4 ? 'bg-emerald-500 text-white ring-8 ring-emerald-50 firework-animation' : (roadmapStep > 4 ? 'bg-emerald-600 text-white shadow-lg' : 'bg-white border-2 border-slate-100 text-slate-300')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z">
                                    </path>
                                </svg>
                            </div>
                            <span
                                class="mt-4 text-[10px] font-black transition-colors duration-500 uppercase tracking-widest text-center"
                                :class="roadmapStep === 4 ? 'text-emerald-600 scale-110' : 'text-slate-400'">Handover</span>
                            <span class="mt-1 text-[9px] font-bold text-center h-3"
                                :class="roadmapStep === 4 ? 'text-emerald-500' : 'text-transparent'"
                                x-text="roadmapStep === 4 ? 'Project Completed!' : ''"></span>
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
                                <div class="font-bold text-slate-900">{{ $estimate->currency_symbol }}
                                    {{ number_format($section->total, 2) }}
                                </div>
                            </div>
                        </button>

                        <!-- Section Content (Items) -->
                        <div x-show="activeSection === {{ $section->id }}" x-collapse
                            class="bg-slate-50/50 border-t border-slate-100">
                            <div class="p-4 space-y-4">
                                @foreach($section->items as $item)
                                    <div
                                        class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col sm:flex-row items-start gap-4 transition-all hover:shadow-md group/item">

                                        <!-- Image Column (Fixed Width) -->
                                        @php
                                            $imageUrl = $item->product ? $item->product->primary_image_url : null;
                                        @endphp
                                        @if(!$section->is_package && $imageUrl)
                                            <div @click.stop="openItemImage('{{ $imageUrl }}')"
                                                class="w-full sm:w-24 h-48 sm:h-24 bg-slate-50 rounded-2xl shrink-0 overflow-hidden border border-slate-200 shadow-sm relative group-hover/item:shadow-md transition-all cursor-zoom-in">
                                                <img src="{{ $imageUrl }}"
                                                    class="w-full h-full object-cover transition-transform duration-500 group-hover/item:scale-110">
                                                <div
                                                    class="absolute inset-0 bg-black/0 hover:bg-black/10 transition-colors flex items-center justify-center">
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Content Grid -->
                                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-y-3 sm:gap-4 min-w-0">

                                            <!-- Main Details (Name, Desc) -->
                                            <div class="sm:col-span-5">
                                                <button @click="openItemComments({{ $item->id }})"
                                                    class="font-bold text-slate-900 text-sm mb-1 group-hover/item:text-indigo-600 transition-colors text-left">{{ $item->name }}</button>
                                                <div
                                                    class="text-xs text-slate-500 leading-relaxed prose prose-sm max-w-none prose-p:my-0 prose-p:leading-relaxed">
                                                    {!! $item->description !!}
                                                </div>

                                                <!-- Unit Type Badge if exists -->
                                                @if(!$section->is_package && $item->unit_type)
                                                    <span
                                                        class="inline-flex mt-2 px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-500 rounded uppercase tracking-wide">{{ $item->unit_type }}</span>
                                                @endif
                                            </div>

                                            <!-- Configuration / Variants / Dims (Middle Col) -->
                                            <div class="sm:col-span-4 flex flex-col sm:justify-center space-y-2">
                                                <!-- Variants -->
                                                @if($item->options && is_array($item->options) && count($item->options) > 0)
                                                    <div
                                                        class="flex flex-col gap-1.5 pl-0 sm:pl-3 sm:border-l-2 sm:border-slate-100">
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
                                                                <span
                                                                    class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">{{ str_replace('_', ' ', $label) }}</span>
                                                                <span class="text-xs font-semibold text-slate-700">{{ $val }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Dimensions -->
                                                @if(!$section->is_package && ($item->length || $item->width || $item->height))
                                                    <div
                                                        class="flex flex-wrap gap-1.5 pl-0 sm:pl-3 sm:border-l-2 sm:border-slate-100 {{ ($item->options && count($item->options) > 0) ? '-mt-1' : '' }}">
                                                        @if($item->length)
                                                            <div
                                                                class="px-1.5 py-0.5 border border-slate-200 rounded text-[10px] text-slate-500 bg-slate-50">
                                                                <span class="font-bold text-slate-700">L:</span> {{ $item->length + 0 }}
                                                        </div>@endif
                                                        @if($item->width)
                                                            <div
                                                                class="px-1.5 py-0.5 border border-slate-200 rounded text-[10px] text-slate-500 bg-slate-50">
                                                                <span class="font-bold text-slate-700">W:</span> {{ $item->width + 0 }}
                                                        </div>@endif
                                                        @if($item->height)
                                                            <div
                                                                class="px-1.5 py-0.5 border border-slate-200 rounded text-[10px] text-slate-500 bg-slate-50">
                                                                <span class="font-bold text-slate-700">H:</span> {{ $item->height + 0 }}
                                                        </div>@endif
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Price (Right Col) -->
                                            <div
                                                class="sm:col-span-3 flex flex-row sm:flex-col justify-between sm:justify-center sm:items-end items-center mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-0 border-slate-50">
                                                <!-- Mobile Label -->
                                                <div class="sm:hidden text-xs text-slate-400 font-bold uppercase">Total</div>

                                                <div class="text-right">
                                                    <div class="text-sm font-black text-slate-900">
                                                        {{ $estimate->currency_symbol }} {{ number_format($item->total, 2) }}
                                                    </div>

                                                    <!-- Item Actions -->
                                                    <div class="mt-2 flex justify-end">
                                                        <button @click="openItemComments({{ $item->id }})"
                                                            class="group flex items-center gap-1.5 px-2 py-1 rounded-2xl hover:bg-indigo-50 transition-colors"
                                                            title="Discuss this item">
                                                            <div class="relative">
                                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500"
                                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                                                    </path>
                                                                </svg>
                                                                @if($item->comments->where('type', '!=', 'internal')->count() > 0)
                                                                    <span class="absolute -top-1.5 -right-1.5 flex h-3 w-3">
                                                                        <span
                                                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                                                        <span
                                                                            class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <span
                                                                class="text-[10px] font-bold text-slate-400 group-hover:text-indigo-600">Discuss</span>
                                                        </button>
                                                    </div>
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

            <!-- Comments Thread Removed per user request (moved to modal) -->

            <!-- Terms & Conditions Button -->

            <!-- Terms & Conditions Button -->
            @if($estimate->has_final_terms)
                <div class="p-6 sm:p-8 flex justify-center border-t border-slate-100">
                    <button @click="showTermsModal = true"
                        class="group flex items-center gap-3 px-6 py-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-200 transition-all">
                        <div
                            class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <div class="text-sm font-bold text-slate-900 leading-tight">Terms & Conditions</div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Click to view full
                                document</div>
                        </div>
                        <svg class="w-5 h-5 text-slate-300 group-hover:text-indigo-400 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            @endif

        </div>

        <!-- Sticky Mobile Actions -->
        @if(!in_array($estimate->client_status, ['accepted', 'declined']) && !in_array($estimate->estimate_status, ['accepted', 'declined']) && !$estimate->isExpired())
            <div
                class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] flex gap-2 z-30 sm:hidden">
                <a href="{{ URL::signedRoute('portal.download', $estimate) }}"
                    class="flex-1 py-3 text-slate-700 font-bold text-xs bg-slate-50 border border-slate-200 rounded-xl text-center flex items-center justify-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    PDF
                </a>
                <button @click="showDeclineModal = true"
                    class="flex-1 py-3 text-red-600 font-bold text-xs bg-red-50 rounded-xl">Decline</button>
                <button @click="showRequestChangesModal = true"
                    class="flex-1 py-3 text-indigo-600 font-bold text-xs bg-indigo-50 rounded-xl">Request Change</button>
                <button @click="showAcceptModal = true"
                    class="flex-[2] py-3 bg-slate-900 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-500/30">Accept
                    Estimate</button>
            </div>

        <!-- Desktop Actions -->
            <div class="hidden sm:flex justify-end gap-3 mb-10">
                @if(!$estimate->isExpired())
                    <a href="{{ URL::signedRoute('portal.download', $estimate) }}"
                        class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg font-semibold hover:bg-slate-50 transition-colors">Download
                        PDF</a>
                    <button @click="showDeclineModal = true"
                        class="px-4 py-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg font-semibold transition-colors">Decline</button>
                    <button @click="showRequestChangesModal = true"
                        class="px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg font-bold hover:bg-indigo-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Request for Change
                    </button>
                    <button @click="showAcceptModal = true"
                        class="px-6 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold shadow-lg transition-all transform hover:-translate-y-0.5">Accept
                        Estimate</button>
                @endif
            </div>
        @elseif($estimate->client_status === 'accepted' || $estimate->estimate_status === 'accepted')
            <!-- Execution Details Card -->
            <div
                class="mb-12 bg-white rounded-[2.5rem] p-8 sm:p-12 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 animate-fade-in relative overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Left Column: Authenticity Seal -->
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="relative mb-6">
                            <svg viewBox="0 0 200 200"
                                class="w-48 h-48 sm:w-64 sm:h-64 text-emerald-500 fill-current drop-shadow-xl filter drop-shadow-[0_4px_10px_rgba(16,185,129,0.2)]">
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
                                        <textPath href="#circlePath-v2" startOffset="50%" text-anchor="middle">APPROVED
                                        </textPath>
                                    </text>
                                    <rect x="15" y="82" width="170" height="36" rx="4" fill="currentColor" />
                                    <text x="100" y="108" text-anchor="middle"
                                        class="text-white text-[22px] font-black uppercase tracking-[0.1em]">APPROVED</text>
                                    <path d="M 40,100 A 60,60 0 1,0 160,100" fill="none" id="datePath-v2" />
                                    <text class="text-[11px] font-bold uppercase tracking-[0.3em]">
                                        <textPath href="#datePath-v2" startOffset="50%" text-anchor="middle"
                                            dominant-baseline="hanging">
                                            {{ $estimate->signed_at ? $estimate->signed_at->format('M d, Y') : '' }}
                                        </textPath>
                                    </text>
                                </g>
                            </svg>
                        </div>
                        <div
                            class="inline-flex items-center px-4 py-1.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-black uppercase tracking-widest border border-emerald-200">
                            Verified Record
                        </div>
                    </div>

                    <!-- Right Column: Execution Information -->
                    <div class="space-y-8">
                        <div>
                            <h3
                                class="text-lg font-black text-slate-900 uppercase tracking-widest border-b-2 border-slate-900 inline-block pb-1 mb-6">
                                Approval Details</h3>

                            <div class="space-y-6">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Client
                                        Signature</p>
                                    <div
                                        class="bg-slate-50 border border-slate-100 rounded-2xl p-6 relative group overflow-hidden">
                                        <div
                                            class="absolute inset-0 bg-gradient-to-br from-white/50 to-transparent pointer-events-none">
                                        </div>
                                        @if($estimate->signature)
                                            <img src="{{ $estimate->signature }}" alt="Client Signature"
                                                class="max-h-20 w-auto grayscale contrast-125 mix-blend-multiply mx-auto transition-transform group-hover:scale-105 duration-500">
                                            <div class="absolute bottom-2 right-4 text-[8px] font-mono text-slate-300">Verified
                                                ID: #{{ str_pad($estimate->id, 8, '0', STR_PAD_LEFT) }}</div>
                                        @else
                                            <div
                                                class="h-20 flex items-center justify-center text-slate-300 italic text-sm text-[11px] uppercase font-bold tracking-widest">
                                                Digital Signature Missing</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Date
                                            Signed</p>
                                        <p class="text-sm font-bold text-slate-900 leading-tight">
                                            {{ $estimate->signed_at ? $estimate->signed_at->format('H:i T') : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">IP
                                            Address</p>
                                        <p class="text-sm font-bold text-slate-900 leading-tight">
                                            {{ $estimate->signer_ip ?? '127.0.0.1' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-4">
                            <a href="{{ URL::signedRoute('portal.download', $estimate) }}"
                                class="flex items-center justify-center gap-3 w-full py-4 bg-slate-900 text-white rounded-2xl font-bold transition-all hover:bg-slate-800 hover:shadow-xl group">
                                <svg class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <span>Download Final PDF with Seal</span>
                            </a>
                            <p class="text-center text-[10px] text-slate-400 mt-4 font-medium italic">A copy of this
                                execution has been emailed to both parties.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modals -->
        <!-- Request Changes Modal -->
        <div x-show="showRequestChangesModal" class="relative z-50" style="display: none;">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-transition.opacity></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-xl transition-all sm:w-full sm:max-w-lg"
                        @click.away="showRequestChangesModal = false">
                        <form action="{{ URL::signedRoute('portal.request-changes', $estimate) }}" method="POST">
                            @csrf
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Request Changes</h3>
                            <p class="text-sm text-slate-500 mb-6 font-medium">Please select the areas that need revision and provide details below.</p>
                            
                            <div class="space-y-4 mb-6">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Revision Checklist</p>
                                <div class="grid grid-cols-1 gap-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                    @foreach($checklists as $checklist)
                                        <label class="flex items-start gap-3 p-3 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer transition-colors border border-transparent hover:border-slate-200 group">
                                            <input type="checkbox" name="checklist[]" value="{{ $checklist->id }}" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">{{ $checklist->task }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Additional Feedback</p>
                                <textarea name="comments" rows="4"
                                    class="w-full rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                    placeholder="Describe the changes you would like to see..."
                                    required></textarea>
                            </div>

                            <!-- Client Identity Capture -->
                            <div class="grid grid-cols-2 gap-3 mt-4" x-show="!hasPostedComment && clientName === ''">
                                <div>
                                    <input type="text" name="client_name" x-model="clientName"
                                        class="w-full rounded-lg border-slate-200 text-xs focus:ring-indigo-500 focus:border-indigo-500 p-2.5 bg-slate-50"
                                        placeholder="Your Name">
                                </div>
                                <div>
                                    <input type="email" name="client_email" x-model="clientEmail"
                                        class="w-full rounded-lg border-slate-200 text-xs focus:ring-indigo-500 focus:border-indigo-500 p-2.5 bg-slate-50"
                                        placeholder="Email Address">
                                </div>
                            </div>

                            <div class="mt-8 flex gap-3">
                                <button type="button" @click="showRequestChangesModal = false"
                                    class="flex-1 py-3 bg-slate-100 text-slate-700 font-bold rounded-xl transition-all hover:bg-slate-200">Close</button>
                                <button type="submit"
                                    class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/30 transition-all hover:bg-indigo-700 transform hover:-translate-y-0.5 active:translate-y-0">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

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
                                            :class="agreedToTerms ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.3)]' : 'bg-slate-200'">
                                        </div>
                                        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-all shadow-sm"
                                            :class="agreedToTerms ? 'translate-x-4' : 'translate-x-0'"></div>
                                    </div>
                                    <span
                                        class="text-[10px] font-black text-slate-600 uppercase tracking-[0.15em] select-none">I
                                        agree to the <button type="button" @click="showTermsModal = true"
                                            class="text-indigo-600 hover:underline">Terms & Conditions</button></span>
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

        <!-- Comment Modal (General & Item) -->
        <div x-show="showCommentModal" class="relative z-50" style="display: none;"
            @keydown.escape.window="showCommentModal = false; activeItemId = null">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-transition.opacity></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-xl flex flex-col max-h-[90vh]"
                        @click.away="showCommentModal = false; activeItemId = null">

                        <!-- Header -->
                        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900"
                                    x-text="activeItemId ? 'Item Discussion' : 'General Comments'"></h3>
                                <p class="text-xs text-slate-500"
                                    x-text="activeItemId ? 'Discussing specific item details' : 'Ask questions about the estimate'">
                                </p>
                            </div>
                            <button @click="showCommentModal = false; activeItemId = null"
                                class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Thread -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50/50 min-h-[300px] max-h-[60vh] scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100"
                            id="comment-thread-container">
                            <!-- Empty State -->
                            <div x-show="getComments().length === 0"
                                class="flex flex-col items-center justify-center h-full text-slate-400 py-10">
                                <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                    </path>
                                </svg>
                                <p class="text-sm">No comments yet. Start the conversation!</p>
                            </div>

                            <!-- Message Loop -->
                            <div>
                                <template x-for="(comment, index) in getComments()" :key="comment.id + '-' + index">
                                    <div :class="comment.type === 'client' ? 'flex justify-end' : 'flex justify-start'">
                                        <div class="max-w-[85%]">
                                            <div class="flex items-end gap-2 mb-1"
                                                :class="comment.type === 'client' ? 'justify-end' : ''">
                                                <div x-show="comment.type !== 'client'" class="flex flex-col">
                                                    <span class="text-xs font-bold text-slate-700"
                                                        x-text="comment.user ? comment.user.name : 'Team'"></span>
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-medium"
                                                    x-text="new Date(comment.created_at).toLocaleString([], {month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit'})"></span>
                                                <span x-show="comment.type === 'client'"
                                                    class="text-xs font-bold text-slate-700">You</span>
                                            </div>

                                            <!-- Item Reference Badge (If viewing general thread but comment is on item) -->
                                            <div x-show="!activeItemId && comment.commentable_type && comment.commentable_type.includes('EstimateItem') && comment.item_name"
                                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-indigo-500 bg-indigo-50 px-2 py-1 rounded inline-block cursor-pointer hover:bg-indigo-100 transition-colors"
                                                @click="openItemComments(comment.commentable_id)">
                                                On Item: <span x-text="comment.item_name"></span> &rarr;
                                            </div>

                                            <div class="p-3 rounded-2xl shadow-sm text-sm leading-relaxed"
                                                :class="comment.type === 'client' ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-700 rounded-tl-none'">
                                                <p x-html="comment.comment.replace(/\n/g, '<br>')"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Footer (Input) -->
                        <div class="p-4 bg-white border-t border-slate-100">
                            <form @submit.prevent="submitComment()" class="space-y-3">
                                <div class="flex gap-2">
                                    <textarea x-model="newComment" rows="1"
                                        class="flex-1 rounded-2xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 resize-none py-3 px-4 text-sm"
                                        required placeholder="Type a message..."></textarea>
                                    <button type="submit" :disabled="isSubmitting"
                                        class="p-3 bg-indigo-600 text-white rounded-2xl hover:bg-indigo-700 shadow-md transition-colors flex items-center justify-center disabled:opacity-50">
                                        <svg x-show="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        <svg x-show="isSubmitting" class="animate-spin w-5 h-5" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-4"
                                    x-show="!hasPostedComment && getComments().length === 0">
                                    <div>
                                        <input type="text" x-model="clientName"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:ring-indigo-500 focus:border-indigo-500 p-2"
                                            placeholder="Your Name (Optional)">
                                    </div>
                                    <div>
                                        <input type="email" x-model="clientEmail"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:ring-indigo-500 focus:border-indigo-500 p-2"
                                            placeholder="Email (Optional)">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Modal -->
        <div x-show="showVideoModal" class="relative z-[60]" style="display: none;"
            @keydown.escape.window="showVideoModal = false">
            <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-xl transition-opacity" x-transition.opacity
                @click="showVideoModal = false"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-5xl aspect-video bg-black rounded-3xl overflow-hidden shadow-[0_0_100px_rgba(99,102,241,0.2)] border border-white/10"
                        @click.stop>
                        <button @click="showVideoModal = false"
                            class="absolute top-6 right-6 z-20 w-12 h-12 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-md transition-all border border-white/20 hover:scale-110">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        @if($isYoutube)
                            <iframe id="youtube-player" class="w-full h-full" data-src="{{ $videoUrl }}" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                        @else
                            <video x-ref="companyVideo" class="w-full h-full"
                                data-src="{{ Str::startsWith($videoUrl, 'http') ? $videoUrl : asset($videoUrl) }}" controls>
                                Your browser does not support the video tag.
                            </video>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Gallery Lightbox -->
        <div x-show="showImageModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/95 backdrop-blur-xl"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @keydown.window.escape="showImageModal = false" @keydown.window.left="prevImage()"
            @keydown.window.right="nextImage()" style="display: none;">

            <!-- Close Button -->
            <button @click="showImageModal = false"
                class="absolute top-6 right-6 text-white/50 hover:text-white z-10 p-2 bg-white/10 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>

            <!-- Navigation Arrows -->
            <button @click="prevImage()" x-show="lightboxImages.length > 1"
                class="absolute left-6 top-1/2 -translate-y-1/2 p-4 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button @click="nextImage()" x-show="lightboxImages.length > 1"
                class="absolute right-6 top-1/2 -translate-y-1/2 p-4 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>

            <!-- Image Container -->
            <div class="relative max-w-[90vw] max-h-[85vh] flex flex-col items-center gap-6"
                @click.away="showImageModal = false">
                <img :src="lightboxImages[currentImageIndex] && lightboxImages[currentImageIndex].startsWith('http') ? lightboxImages[currentImageIndex] : '{{ asset('') }}' + (lightboxImages[currentImageIndex] || '').replace(/^\//, '')"
                    class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl transition-all duration-500 transform animate-scale-in">

                <!-- Counter & Title -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-2 rounded-full border border-white/10 flex items-center gap-4"
                    x-show="lightboxImages.length > 1">
                    <span class="text-white text-xs font-bold uppercase tracking-widest whitespace-nowrap">Image <span
                            x-text="currentImageIndex + 1"></span> / <span x-text="lightboxImages.length"></span></span>
                </div>
            </div>
        </div>

        <!-- Terms Modal -->
        <div x-show="showTermsModal" class="relative z-[70]" style="display: none;"
            @keydown.escape.window="showTermsModal = false">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" x-transition.opacity
                @click="showTermsModal = false"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-3xl bg-white/95 backdrop-blur-xl rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-white/20 flex flex-col max-h-[85vh] transition-all transform animate-scale-in"
                        @click.stop>
                        <!-- Premium Header -->
                        <div
                            class="p-8 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white/50 backdrop-blur-md z-10 rounded-t-[2.5rem]">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center text-white shadow-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-black text-slate-900 uppercase tracking-widest leading-none">
                                        Terms & Conditions</h3>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Rules
                                        & Agreement</p>
                                </div>
                            </div>
                            <button @click="showTermsModal = false"
                                class="w-10 h-10 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center hover:bg-slate-200 transition-colors focus:ring-2 focus:ring-slate-200 outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Content Area -->
                        <div class="p-10 overflow-y-auto">
                            <div
                                class="prose prose-slate max-w-none text-slate-600 leading-relaxed text-sm prose-p:mb-4 prose-headings:text-slate-900 prose-headings:font-black prose-headings:uppercase prose-headings:tracking-tighter prose-strong:text-slate-900 prose-strong:font-black">
                                {!! $estimate->final_terms_html !!}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="p-8 border-t border-slate-100 flex justify-end bg-slate-50/50 rounded-b-[2.5rem] items-center gap-4">
                            <p
                                class="hidden sm:block text-[9px] font-black text-slate-400 uppercase tracking-widest italic">
                                Scroll to read the complete document</p>
                            <button @click="showTermsModal = false"
                                class="px-8 py-3 bg-slate-900 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 active:translate-y-0">Close</button>
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
                    // UI State
                    showCommentModal: false,
                    showRequestChangesModal: false,
                    showDeclineModal: false,
                    showAcceptModal: false,
                    showVideoModal: false,
                    showTermsModal: false,
                    showImageModal: false,
                    activeSection: null,
                    agreedToTerms: false,
                    roadmapStep: 0,

                    // Library Instances
                    signaturePad: null,

                    // Data
                    projectImages: @json($showcaseImages),
                    lightboxImages: [],
                    currentImageIndex: 0,

                    // Comment System
                    activeItemId: null,
                    allComments: @json($comments),
                    hasPostedComment: false,
                    newComment: '',
                    clientName: '',
                    clientEmail: '',
                    isSubmitting: false,

                    // Expiry/Countdown
                    expiryDate: @json($estimate->expiry_date),
                    countdown: { days: 0, hours: 0, minutes: 0, seconds: 0, expired: false },

                    // Chart Data
                    chartLabels: @json($estimate->sections->pluck('name')),
                    chartTotals: @json($estimate->sections->pluck('total')),

                    init() {
                        // Project Images fallback check
                        if (!this.projectImages || this.projectImages.length === 0) {
                            this.projectImages = {!! json_encode($showcaseImages) !!};
                        }

                        // Initialize Charts, Countdown & Roadmap Animation
                        this.$nextTick(() => {
                            this.renderChart();
                            this.updateCountdown();
                            setInterval(() => this.updateCountdown(), 1000);

                            // Roadmap Simulation
                            setInterval(() => {
                                this.roadmapStep = (this.roadmapStep + 1) % 5;
                            }, 1500);
                        });

                        // Watch for Acceptance Modal to init Signature Pad
                        this.$watch('showAcceptModal', value => {
                            if (value) {
                                this.$nextTick(() => {
                                    const canvas = document.getElementById('signature-pad');
                                    if (canvas && typeof SignaturePad !== 'undefined') {
                                        this.signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
                                        window.addEventListener('resize', () => this.resizeCanvas(canvas));
                                        this.resizeCanvas(canvas);
                                    }
                                });
                            }
                        });

                        // Watch for Video Modal
                        this.$watch('showVideoModal', value => {
                            if (value) {
                                if (this.$refs.companyVideo) {
                                    if (!this.$refs.companyVideo.src) {
                                        this.$refs.companyVideo.src = this.$refs.companyVideo.dataset.src;
                                    }
                                    this.$nextTick(() => { this.$refs.companyVideo.play(); });
                                }
                                const yt = document.getElementById('youtube-player');
                                if (yt && (!yt.src || yt.src === 'about:blank' || yt.src === '')) {
                                    yt.src = yt.dataset.src;
                                }
                            } else {
                                if (this.$refs.companyVideo) this.$refs.companyVideo.pause();
                                const yt = document.getElementById('youtube-player');
                                if (yt) yt.src = '';
                            }
                        });

                        // Watch for Image Lightbox
                        this.$watch('showImageModal', value => {
                            document.body.style.overflow = value ? 'hidden' : '';
                        });
                    },

                    // Methods
                    updateCountdown() {
                        if (!this.expiryDate) return;
                        const now = new Date().getTime();
                        const expiry = new Date(this.expiryDate).getTime();
                        const distance = expiry - now;

                        if (distance < 0) {
                            this.countdown.expired = true;
                            return;
                        }

                        this.countdown.days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        this.countdown.hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        this.countdown.minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        this.countdown.seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    },

                    renderChart() {
                        const ctx = document.getElementById('costBreakdownChart');
                        if (!ctx) return;

                        // Build dataset: Use sections if multiple, otherwise subtotal/tax
                        let labels = this.chartLabels;
                        let data = this.chartTotals;

                        if (labels.length === 0) {
                            labels = ['Subtotal', 'Tax'];
                            data = [{{ $estimate->subtotal }}, {{ $estimate->total_tax }}];
                        }

                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: data,
                                    backgroundColor: [
                                        '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#3b82f6', '#94a3b8'
                                    ],
                                    borderWidth: 0,
                                    hoverOffset: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '75%',
                                plugins: {
                                    legend: {
                                        position: window.innerWidth < 640 ? 'bottom' : 'right',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 20,
                                            font: { family: 'Inter', size: 11, weight: 'bold' },
                                            color: '#64748b'
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: '#1e293b',
                                        padding: 12,
                                        titleFont: { size: 14, weight: 'bold' },
                                        bodyFont: { size: 12 },
                                        cornerRadius: 8,
                                        displayColors: false
                                    }
                                }
                            }
                        });
                    },

                    getComments() {
                        if (this.activeItemId) {
                            return this.allComments.filter(c =>
                                c.commentable_type &&
                                c.commentable_type.includes('EstimateItem') &&
                                c.commentable_id === this.activeItemId
                            );
                        }
                        return this.allComments;
                    },

                    openItemComments(itemId) {
                        this.activeItemId = itemId;
                        this.showCommentModal = true;
                        this.$nextTick(() => {
                            const el = document.getElementById('comment-thread-container');
                            if (el) el.scrollTop = el.scrollHeight;
                        });
                    },

                    async submitComment() {
                        if (!this.newComment.trim() || this.isSubmitting) return;
                        this.isSubmitting = true;

                        const commentText = this.newComment;
                        const tempId = 'temp-' + Date.now();

                        this.allComments.push({
                            id: tempId,
                            comment: commentText,
                            type: 'client',
                            created_at: new Date().toISOString(),
                            client_name: this.clientName || 'You',
                            commentable_type: this.activeItemId ? 'App\\Models\\EstimateItem' : 'App\\Models\\Estimate',
                            commentable_id: this.activeItemId || {{ $estimate->id }}
                        });

                        this.newComment = '';
                        this.hasPostedComment = true;
                        this.$nextTick(() => {
                            const el = document.getElementById('comment-thread-container');
                            if (el) el.scrollTop = el.scrollHeight;
                        });

                        try {
                            const response = await fetch("{{ URL::signedRoute('portal.comment', $estimate) }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    item_id: this.activeItemId,
                                    comment: commentText,
                                    client_name: this.clientName,
                                    client_email: this.clientEmail
                                })
                            });

                            const data = await response.json();
                            if (!data.success) throw new Error('Failed');
                        } catch (e) {
                            console.error(e);
                            alert('Failed to post comment.');
                            this.allComments = this.allComments.filter(c => c.id !== tempId);
                            this.newComment = commentText;
                        } finally {
                            this.isSubmitting = false;
                        }
                    },

                    openProjectGallery(index) {
                        this.lightboxImages = this.projectImages;
                        this.currentImageIndex = index;
                        this.showImageModal = true;
                    },

                    openItemImage(url) {
                        this.lightboxImages = [url];
                        this.currentImageIndex = 0;
                        this.showImageModal = true;
                    },

                    nextImage() {
                        this.currentImageIndex = (this.currentImageIndex + 1) % this.lightboxImages.length;
                    },

                    prevImage() {
                        this.currentImageIndex = (this.currentImageIndex - 1 + this.lightboxImages.length) % this.lightboxImages.length;
                    },

                    resizeCanvas(canvas) {
                        if (!this.signaturePad) return;
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        canvas.getContext('2d').scale(ratio, ratio);
                        this.signaturePad.clear();
                    },

                    toggleSection(id) {
                        this.activeSection = this.activeSection === id ? null : id;
                    }
                }));
            });
        </script>
    </div>
</x-portal-layout>