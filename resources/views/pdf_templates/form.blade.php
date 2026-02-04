<x-app-layout>
    <div class="h-[calc(100vh-64px)] flex flex-col">
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold text-gray-900">{{ $title }}</h1>
                {!! $header_actions ?? '' !!}
            </div>
            <div class="flex gap-2">
                <a href="{{ route('pdf-templates.index') }}"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" form="templateForm"
                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                    {{ $submitLabel ?? 'Save Template' }}
                </button>
            </div>
        </div>

        <form id="templateForm" action="{{ $action }}" method="POST" class="flex-1 flex overflow-hidden"
            x-data="templateEditor()">
            @csrf
            @if($method ?? false)
                @method($method)
            @endif

            <!-- Sidebar -->
            @include('pdf_templates.partials.settings-sidebar')

            <!-- Editor Area -->
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
                    @include('pdf_templates.partials.toolbar')
                </div>

                <!-- Content Area -->
                <div class="flex-1 relative">
                    @include('pdf_templates.partials.visual-editor')
                    @include('pdf_templates.partials.code-editor')
                    @include('pdf_templates.partials.preview-pane')
                </div>
            </div>
        </form>
    </div>

    @include('pdf_templates.partials.history-modal')

    @push('scripts')
        @include('pdf_templates.partials.scripts')
    @endpush
</x-app-layout>