<x-app-layout>
    <!-- Header with Breadcrumbs & Action -->
    <div class="mb-10 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <nav class="flex mb-3" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2.5">
                        <li>
                            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="h-5 w-5 flex-shrink-0 text-slate-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-2 text-sm font-semibold text-slate-900 tracking-tight">Clients</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Client <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Directory</span>
                </h1>
                <p class="mt-2 text-base text-slate-500 max-w-2xl leading-relaxed">Manage your clients, track their estimates, and monitor business growth.</p>
            </div>
            <div class="flex items-center">
                <a href="{{ route('clients.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-6 py-3.5 text-sm font-bold text-white shadow-xl shadow-slate-200 hover:bg-indigo-600 hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="-ml-1 mr-2.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Client
                </a>
            </div>
        </div>
    </div>

    <!-- Executive Intelligence Layer (Stats) -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-10 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-100">
        <!-- Card 1 -->
        <div class="group relative overflow-hidden rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:shadow-xl hover:ring-indigo-100">
            <div class="absolute top-0 right-0 -mr-4 -mt-4 h-32 w-32 rounded-full bg-slate-50 opacity-50 transition-all duration-500 group-hover:scale-150 group-hover:bg-indigo-50"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="p-3 rounded-2xl bg-slate-100 text-slate-600 group-hover:bg-slate-900 group-hover:text-white transition-all duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Clients</span>
                </div>
                <div class="mt-6">
                    <div class="text-4xl font-black text-slate-900 tracking-tight">{{ $stats['total'] }}</div>
                    <div class="mt-2 flex items-center text-sm font-medium text-slate-500">
                        <span class="text-emerald-500 flex items-center mr-1.5 font-bold">
                            Records active
                        </span>
                        in database
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="group relative overflow-hidden rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:shadow-xl hover:ring-indigo-100">
            <div class="absolute top-0 right-0 -mr-4 -mt-4 h-32 w-32 rounded-full bg-indigo-50/50 opacity-50 transition-all duration-500 group-hover:scale-150 group-hover:bg-violet-50"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="p-3 rounded-2xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-widest text-indigo-400">Wins</span>
                </div>
                <div class="mt-6">
                    <div class="text-4xl font-black text-slate-900 tracking-tight">{{ $stats['with_estimates'] }}</div>
                    <div class="mt-2 flex items-center text-sm font-medium text-slate-500">
                        <span class="text-indigo-600 flex items-center mr-1.5 font-bold italic">
                            {{ number_format(($stats['total'] > 0 ? ($stats['with_estimates'] / $stats['total']) * 100 : 0), 0) }}% Conversion
                        </span>
                        rate to estimate
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="group relative overflow-hidden rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:shadow-xl hover:ring-emerald-100">
            <div class="absolute top-0 right-0 -mr-4 -mt-4 h-32 w-32 rounded-full bg-emerald-50/50 opacity-50 transition-all duration-500 group-hover:scale-150 group-hover:bg-emerald-100"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-500">Growth</span>
                </div>
                <div class="mt-6">
                    <div class="text-4xl font-black text-slate-900 tracking-tight">{{ $stats['recent'] }}</div>
                    <div class="mt-2 flex items-center text-sm font-medium text-slate-500">
                        <span class="text-emerald-600 flex items-center mr-1.5 font-bold">
                            +{{ $stats['recent'] }} New
                        </span>
                        in the last 30 days
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic discovery Layer (Filters) -->
    <div class="bg-white/60 backdrop-blur-xl rounded-3xl shadow-sm ring-1 ring-slate-200 p-6 mb-8 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-200">
        <form action="{{ route('clients.index') }}" method="GET" class="flex flex-col gap-6 lg:flex-row lg:items-end">
            <div class="flex-1 min-w-0">
                <label for="search" class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 ml-1">Search</label>
                <div class="relative group">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 transition-colors group-focus-within:text-indigo-600">
                        <svg class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        class="block w-full rounded-2xl border-0 py-4 pl-12 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all bg-slate-50/50 group-hover:bg-white"
                        placeholder="Search by name, company, email or city...">
                </div>
            </div>

            <div class="w-full lg:w-56">
                <label for="city" class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 ml-1">City Filter</label>
                <select id="city" name="city"
                    class="block w-full rounded-2xl border-0 py-4 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all bg-slate-50/50 hover:bg-white cursor-pointer px-4">
                    <option value="">All Regions</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full lg:w-56">
                <label for="has_estimates" class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 ml-1">Status Filter</label>
                <select id="has_estimates" name="has_estimates"
                    class="block w-full rounded-2xl border-0 py-4 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all bg-slate-50/50 hover:bg-white cursor-pointer px-4">
                    <option value="">Status: All</option>
                    <option value="yes" {{ request('has_estimates') == 'yes' ? 'selected' : '' }}>Converting (Ests.)</option>
                    <option value="no" {{ request('has_estimates') == 'no' ? 'selected' : '' }}>Leads (No Ests.)</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-8 py-4 text-sm font-bold text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all duration-300">
                    Apply
                </button>
                @if(request()->anyFilled(['search', 'city', 'has_estimates']))
                    <a href="{{ route('clients.index') }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-4 text-sm font-bold text-slate-600 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition-all">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Presentation Layer -->
    <div class="bg-white/60 backdrop-blur-xl shadow-sm ring-1 ring-slate-200 rounded-[2.5rem] overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-700 delay-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-white/40 backdrop-blur-md">
                    <tr>
                        <th scope="col" class="px-8 py-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Client Profile</th>
                        <th scope="col" class="px-8 py-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Company</th>
                        <th scope="col" class="px-8 py-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Contact Details</th>
                        <th scope="col" class="px-8 py-6 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Activity</th>
                        <th scope="col" class="relative px-8 py-6">
                            <span class="sr-only">Quick Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="client-table-body" class="divide-y divide-slate-50 transition-opacity duration-300">
                    @include('clients._table')
                </tbody>
            </table>
        </div>
    </div>

    @if($clients->hasPages())
        <div class="mt-12 animate-in fade-in duration-1000">
            {{ $clients->links() }}
        </div>
    @endif

    <style>
        .limit-text-150 {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (min-width: 1280px) {
            .limit-text-150 { max-width: 250px; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            const citySelect = document.getElementById('city');
            const statusSelect = document.getElementById('has_estimates');
            const tableBody = document.getElementById('client-table-body');
            let timeout = null;

            function performSearch() {
                const search = searchInput ? searchInput.value : '';
                const city = citySelect ? citySelect.value : '';
                const status = statusSelect ? statusSelect.value : '';
                
                if (!tableBody) return;
                tableBody.classList.add('opacity-50');

                const url = new URL(window.location.origin + window.location.pathname);
                url.searchParams.set('search', search);
                url.searchParams.set('city', city);
                url.searchParams.set('has_estimates', status);
                
                // Update URL in browser without reloading
                window.history.pushState({}, '', url);

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    tableBody.innerHTML = html;
                    tableBody.classList.remove('opacity-50');
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.classList.remove('opacity-50');
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(performSearch, 300);
                });
            }

            if (citySelect) citySelect.addEventListener('change', performSearch);
            if (statusSelect) statusSelect.addEventListener('change', performSearch);
        });
    </script>
</x-app-layout>