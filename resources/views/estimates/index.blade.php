<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Estimates</h1>
            <p class="mt-2 text-sm text-slate-500">Manage and track your estimates, status, and client proposals.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-2">
            <button type="button" id="batchDownloadBtn" onclick="submitBatchDownload()" style="display: none;"
                class="rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-all duration-200">
                Download Selected
            </button>
            <form id="batchDownloadForm" action="{{ route('estimates.batch-download') }}" method="POST" class="hidden">
                @csrf
            </form>
            @can('create', App\Models\Estimate::class)
                <a href="{{ route('estimates.create') }}"
                    class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all duration-200">
                    Create Estimate
                </a>
            @endcan
        </div>
    </div>

    <div class="mt-8">
        <!-- Status Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                @foreach(['all', 'draft', 'waiting_approval', 'approved', 'sent', 'accepted', 'declined'] as $status)
                    <a href="{{ route('estimates.index', ['status' => $status]) }}"
                        class="{{ request('status', 'all') == $status ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium capitalize">
                        {{ str_replace('_', ' ', $status) }}
                        <span
                            class="{{ request('status', 'all') == $status ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-900' }} ml-3 hidden rounded-full py-0.5 px-2.5 text-xs font-medium md:inline-block">
                            {{ $counts[$status] ?? 0 }}
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="flow-root">
            <!-- Mobile Card View -->
            <div class="grid grid-cols-1 gap-4 sm:hidden mb-6">
                @forelse ($estimates as $estimate)
                    <x-card padding="4" class="space-y-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <a href="{{ route('estimates.show', $estimate) }}"
                                    class="text-sm font-semibold text-slate-900 block mt-0.5 hover:text-indigo-600 transition-colors">
                                    Estimate #{{ $estimate->estimate_number }}
                                </a>
                            </div>
                            <x-estimate-status-badge :status="$estimate->status" />
                        </div>

                        <div
                            class="flex justify-between items-center text-sm text-slate-500 border-t border-slate-100 pt-3">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</span>
                                <span class="text-slate-900 font-semibold">{{ $estimate->currency }}
                                    {{ number_format($estimate->grand_total, 2) }}</span>
                            </div>
                            <div class="flex flex-col text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Date</span>
                                <span class="text-slate-700">{{ $estimate->estimate_date->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <a href="{{ route('estimates.edit', $estimate) }}"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Edit</a>
                            <form action="{{ route('estimates.destroy', $estimate) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Delete this estimate?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-sm font-medium text-rose-600 hover:text-rose-500 bg-transparent border-0 p-0">Delete</button>
                            </form>
                        </div>
                    </x-card>
                @empty
                    <x-empty-state title="No estimates found" description="Get started by creating your first estimate."
                        actionText="Create Estimate" actionLink="{{ route('estimates.create') }}" />
                @endforelse
            </div>

            <div class="hidden sm:block">
                <x-card padding="0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="relative px-7 sm:w-12 sm:px-6">
                                        <input type="checkbox" id="selectAll"
                                            class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                    </th>
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:pl-6">
                                        Estimate #</th>

                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Type</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Date</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Status</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Total</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse ($estimates as $estimate)
                                    <tr class="group hover:bg-slate-50/50 transition-colors duration-150">
                                        <td class="relative px-7 sm:w-12 sm:px-6">
                                            <input type="checkbox" name="selected_estimates[]" value="{{ $estimate->id }}"
                                                class="estimate-checkbox absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                        </td>
                                        <td
                                            class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-indigo-600 sm:pl-6">
                                            <a href="{{ route('estimates.show', $estimate) }}"
                                                class="hover:text-indigo-900 border-b border-dashed border-indigo-300 hover:border-indigo-900 pb-0.5 transition-colors">
                                                {{ $estimate->estimate_number }}
                                            </a>
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                            <span
                                                class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                                {{ ucfirst(str_replace('_', ' ', $estimate->type)) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                            {{ $estimate->estimate_date->format('M d, Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            <x-estimate-status-badge :status="$estimate->status" />
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-slate-900">
                                            {{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <div class="flex justify-end gap-x-4">
                                                <a href="{{ route('estimates.edit', $estimate) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 transition-colors">Edit</a>
                                                <form action="{{ route('estimates.destroy', $estimate) }}" method="POST"
                                                    class="inline-block"
                                                    onsubmit="return confirm('Are you sure you want to delete this estimate?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-rose-600 hover:text-rose-900 bg-transparent border-0 cursor-pointer p-0 transition-colors">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <x-empty-state title="No estimates found"
                                                description="Get started by creating your first estimate."
                                                actionText="Create Estimate" actionLink="{{ route('estimates.create') }}"
                                                class="rounded-none shadow-none ring-0" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
            <div class="mt-6">
                {{ $estimates->links() }}
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const selectAll = document.getElementById('selectAll');
                const checkboxes = document.querySelectorAll('.estimate-checkbox');
                const batchBtn = document.getElementById('batchDownloadBtn');

                function updateBatchUI() {
                    const checkedCount = document.querySelectorAll('.estimate-checkbox:checked').length;
                    if (checkedCount > 0) {
                        batchBtn.style.display = 'inline-block';
                        batchBtn.textContent = `Download Selected (${checkedCount})`;
                    } else {
                        batchBtn.style.display = 'none';
                    }
                }

                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBatchUI();
                });

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function () {
                        updateBatchUI();
                        // Update Select All state roughly
                        if (!this.checked) selectAll.checked = false;
                    });
                });
            });

            function submitBatchDownload() {
                const form = document.getElementById('batchDownloadForm');
                // Clear previous inputs
                form.querySelectorAll('input[name="estimate_ids[]"]').forEach(el => el.remove());

                const checkboxes = document.querySelectorAll('.estimate-checkbox:checked');
                if (checkboxes.length === 0) return;

                checkboxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'estimate_ids[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });

                form.submit();
            }
        </script>
</x-app-layout>