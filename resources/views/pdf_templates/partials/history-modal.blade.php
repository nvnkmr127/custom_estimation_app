@if($pdfTemplate->exists)
    <div x-data="{ open: false }" @open-history.window="open = true" class="relative z-50" aria-labelledby="modal-title"
        role="dialog" aria-modal="true" x-show="open" style="display: none;">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="open"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6"
                    x-show="open" @click.outside="open = false" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Version History
                            </h3>
                            <div class="mt-2 text-left">
                                <ul role="list" class="divide-y divide-gray-100 max-h-60 overflow-y-auto">
                                    @forelse($pdfTemplate->versions as $version)
                                        <li class="flex justify-between gap-x-6 py-3">
                                            <div class="flex min-w-0 gap-x-4">
                                                <div class="min-w-0 flex-auto">
                                                    <p class="text-sm font-semibold leading-6 text-gray-900">Version
                                                        {{ $version->version }}</p>
                                                    <p class="mt-1 truncate text-xs leading-5 text-gray-500">By
                                                        {{ $version->creator->name ?? 'Unknown' }} •
                                                        {{ $version->created_at->format('M d, Y H:i') }}</p>
                                                </div>
                                            </div>
                                            <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                                                <form
                                                    action="{{ route('pdf-templates.restore', ['pdfTemplate' => $pdfTemplate->id, 'version' => $version->id]) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Restore this version? current unsaved changes will be lost.');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-sm font-semibold leading-6 text-indigo-600 hover:text-indigo-500">Restore</button>
                                                </form>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="py-4 text-center text-sm text-gray-500">No history available yet.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif