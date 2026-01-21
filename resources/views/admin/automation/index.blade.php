<x-app-layout>
    @include('admin.automation.partials.scripts')

    <div x-data="automationPageData()">
        {{-- Modals (Moved specific modal up for debugging) --}}
        @include('admin.automation.modals.visualization')

        {{-- Header & Tabs --}}
        <div class="sm:flex sm:items-center sm:justify-between py-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Automation Rules</h1>
                <p class="mt-2 text-sm text-slate-700">Manage automated workflows and triggers.</p>
            </div>
            <div class="mt-4 sm:mt-0 flex gap-3">
                <!-- Tab Navigation -->
                <div class="flex rounded-md shadow-sm ring-1 ring-inset ring-slate-300">
                    <button @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'"
                        class="px-4 py-2 text-sm font-semibold rounded-l-md transition-colors">
                        Rules
                    </button>
                    <button @click="viewMode = 'analytics'; fetchDashboardAnalytics()"
                        :class="viewMode === 'analytics' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'"
                        class="px-4 py-2 text-sm font-semibold rounded-r-md transition-colors">
                        Analytics
                    </button>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="py-6">
            {{-- Analytics Dashboard --}}
            <div x-show="viewMode === 'analytics'" style="display: none;">
                @include('admin.automation.partials.analytics')
            </div>

            {{-- Rules List --}}
            <div x-show="viewMode === 'list'">
                @include('admin.automation.partials.rules-list')
            </div>
        </div>

        {{-- Modals --}}
        @include('admin.automation.modals.create')
        @include('admin.automation.modals.edit')
        @include('admin.automation.modals.metrics')
        @include('admin.automation.modals.logs')
        @include('admin.automation.modals.templates')


    </div>
</x-app-layout>