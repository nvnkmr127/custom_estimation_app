<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between mb-10">
            <div>
                <h1
                    class="text-3xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">
                    PDF Templates
                </h1>
                <p class="mt-2 text-sm text-gray-500">
                    Design and manage your estimate PDF layouts.
                </p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0">
                <a href="{{ route('pdf-templates.create') }}"
                    class="group inline-flex items-center justify-center rounded-full bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:bg-indigo-500 hover:shadow-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="-ml-1 mr-2 h-5 w-5 transition-transform duration-300 group-hover:rotate-90" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Template
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($templates as $template)
                <div
                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 overflow-hidden flex flex-col">
                    <!-- Preview Placeholder / Top Area -->
                    <div
                        class="h-32 bg-gray-50 border-b border-gray-100 flex items-center justify-center relative overflow-hidden group-hover:bg-indigo-50/30 transition-colors">
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                        <svg class="h-12 w-12 text-gray-300 group-hover:text-indigo-300 transition-colors" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>

                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3
                                class="text-lg font-bold text-gray-900 truncate group-hover:text-indigo-600 transition-colors">
                                {{ $template->name }}
                            </h3>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                @if($template->is_active)
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span> Active
                                @else
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1.5"></span> Inactive
                                @endif
                            </span>
                        </div>

                        <!-- Actions -->
                        <div
                            class="mt-auto flex items-center justify-end space-x-3 pt-4 border-t border-gray-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <a href="{{ route('pdf-templates.edit', $template) }}"
                                class="text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </a>
                            <form action="{{ route('pdf-templates.destroy', $template) }}" method="POST"
                                class="inline-block"
                                onsubmit="return confirm('Are you sure you want to delete this template?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-sm font-medium text-gray-500 hover:text-red-600 transition-colors flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="text-center py-20 bg-white rounded-3xl shadow-sm border border-dashed border-gray-300">
                        <div class="mx-auto h-24 w-24 rounded-full bg-indigo-50 flex items-center justify-center mb-6">
                            <svg class="h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-2 text-xl font-bold text-gray-900">No templates created</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">
                            Create your first PDF template to start customizing your estimate documents.
                        </p>
                        <div class="mt-8">
                            <a href="{{ route('pdf-templates.create') }}"
                                class="inline-flex items-center justify-center rounded-full bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:bg-indigo-500 hover:shadow-indigo-500/50 transition-all duration-300">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Create Template
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $templates->links() }}
        </div>
    </div>
</x-app-layout>