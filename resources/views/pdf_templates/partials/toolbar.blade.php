<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = !open" @click.outside="open = false"
        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
        Insert Snippet
        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
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
                Table (Flat)</button>
            <button type="button" @click="insertSnippet('room_table'); open = false"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Item
                Table (By Room)</button>
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

<div class="relative" x-data="{ open: false, searchTerm: '' }">
    <button type="button" @click="open = !open" @click.outside="open = false"
        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
        Insert Variable
        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clip-rule="evenodd" />
        </svg>
    </button>
    <div x-show="open"
        class="origin-top-left absolute left-0 mt-2 w-72 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10 flex flex-col max-h-96"
        style="display: none;">

        <!-- Search Input -->
        <div class="p-2 border-b border-gray-100">
            <input type="text" x-model="searchTerm" placeholder="Search variables..."
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="py-1 overflow-y-auto flex-1">
            @foreach($variables as $category => $vars)
                <div
                    x-show="!searchTerm || '{{ strtolower($category) }}'.includes(searchTerm.toLowerCase()) || Object.keys({{ json_encode($vars) }}).some(k => k.toLowerCase().includes(searchTerm.toLowerCase()))">
                    <div class="px-4 py-2 text-xs font-bold text-gray-500 uppercase bg-gray-50">{{ $category }}</div>
                    @foreach($vars as $key => $desc)
                        <button type="button"
                            x-show="!searchTerm || '{{ strtolower($key) }}'.includes(searchTerm.toLowerCase())"
                            @click="insertAtCursor('{ {{ $key }} }'); open = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex justify-between group"
                            title="{{ $desc }}">
                            <span class="font-mono text-indigo-600">{ {{ $key }} }</span>
                            <span
                                class="text-xs text-gray-400 hidden group-hover:inline ml-2">{{ Str::limit($desc, 25) }}</span>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>