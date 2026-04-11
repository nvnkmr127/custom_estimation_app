<div x-data="{ 
        isOpen: false, 
        search: '', 
        results: [], 
        loading: false,
        error: '',
        selectedIndex: -1
    }" @open-search.window="isOpen = true; $nextTick(() => $refs.searchInput.focus())"
    @keydown.window.ctrl.k.prevent="$dispatch('open-search')" @keydown.window.meta.k.prevent="$dispatch('open-search')"
    x-show="isOpen" class="relative z-50" role="dialog" aria-modal="true" style="display: none;">
    <!-- Backdrop -->
    <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20">
        <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            @click.away="isOpen = false"
            class="mx-auto max-w-2xl transform divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all">
            <div class="relative">
                <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                        clip-rule="evenodd" />
                </svg>
                <input x-ref="searchInput" type="text"
                    class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm"
                    placeholder="Search estimates, clients, products..." x-model="search" @input.debounce.300ms="
                        if (search.length > 1) {
                            loading = true;
                            error = '';
                            fetch('/search?q=' + encodeURIComponent(search), { headers: { 'Accept': 'application/json' } })
                                .then(res => {
                                    if (!res.ok) throw new Error('Search failed');
                                    return res.json();
                                })
                                .then(data => {
                                    results = data;
                                    loading = false;
                                    selectedIndex = results.length > 0 ? 0 : -1;
                                })
                                .catch(() => {
                                    results = [];
                                    loading = false;
                                    selectedIndex = -1;
                                    error = 'Search is temporarily unavailable. Please try again.';
                                });
                        } else {
                            results = [];
                            error = '';
                            loading = false;
                            selectedIndex = -1;
                        }
                    " @keydown.arrow-down.prevent="if (results.length) selectedIndex = (selectedIndex + 1) % results.length"
                    @keydown.arrow-up.prevent="if (results.length) selectedIndex = (selectedIndex - 1 + results.length) % results.length"
                    @keydown.enter.prevent="if (selectedIndex >= 0 && results.length) window.location.href = results[selectedIndex].url"
                    @keydown.escape="isOpen = false">
            </div>

            <!-- Results -->
            <ul x-show="results.length > 0" class="max-h-96 scroll-py-2 overflow-y-auto py-2 text-sm text-gray-800"
                id="options" role="listbox">
                <template x-for="(result, index) in results" :key="index">
                    <li :class="{ 'bg-indigo-600 text-white': selectedIndex === index }"
                        class="cursor-default select-none px-4 py-2 flex items-center"
                        @mouseenter="selectedIndex = index" @click="window.location.href = result.url">
                        <span class="flex-none h-6 w-6 flex items-center justify-center mr-3"
                            :class="selectedIndex === index ? 'text-white' : 'text-gray-400'">
                            <template x-if="result.icon === 'document-text'">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </template>
                            <template x-if="result.icon === 'user'">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </template>
                            <template x-if="result.icon === 'cube'">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </template>
                        </span>
                        <div class="flex-auto truncate">
                            <span class="font-medium" x-text="result.title"></span>
                            <span class="ml-2 text-xs"
                                :class="selectedIndex === index ? 'text-indigo-100' : 'text-gray-500'"
                                x-text="result.type"></span>
                        </div>
                    </li>
                </template>
            </ul>

            <div x-show="error" class="px-6 py-4 text-sm text-rose-700">
                <span x-text="error"></span>
            </div>

            <!-- Empty state -->
            <div x-show="search.length > 1 && !loading && results.length === 0" class="px-6 py-14 text-center sm:px-14">
                <svg class="mx-auto h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <p class="mt-4 text-sm text-gray-900">No results found for "<span class="font-semibold"
                        x-text="search"></span>".</p>
                <p class="mt-2 text-sm text-gray-500">We couldn’t find anything with that term. Please try again.</p>
            </div>

            <!-- Help text -->
            <div class="flex flex-wrap items-center bg-gray-50 px-4 py-2.5 text-xs text-gray-700">
                Press <kbd
                    class="mx-1 flex h-5 items-center justify-center rounded border bg-white px-1.5 font-semibold text-gray-900 sm:mx-2">⌘</kbd><kbd
                    class="mx-1 flex h-5 items-center justify-center rounded border bg-white px-1.5 font-semibold text-gray-900 sm:mx-2">K</kbd>
                or <kbd
                    class="mx-1 flex h-5 items-center justify-center rounded border bg-white px-1.5 font-semibold text-gray-900 sm:mx-2">Ctrl</kbd><kbd
                    class="mx-1 flex h-5 items-center justify-center rounded border bg-white px-1.5 font-semibold text-gray-900 sm:mx-2">K</kbd>
                to search and <kbd
                    class="mx-1 flex h-5 w-5 items-center justify-center rounded border bg-white font-semibold text-gray-900 sm:mx-2">esc</kbd>
                to close.
            </div>
        </div>
    </div>
</div>
