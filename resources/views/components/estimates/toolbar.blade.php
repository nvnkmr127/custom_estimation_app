@props(['estimate', 'checklists' => [], 'userApproval' => false, 'declineReasons' => []])

<div class="mb-4">
    <nav class="flex" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-2">
            <li>
                <div>
                    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-500">
                        <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Dashboard</span>
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-slate-300" fill="currentColor" viewBox="0 0 20 20"
                        aria-hidden="true">
                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    <a href="{{ route('estimates.index') }}"
                        class="ml-2 text-sm font-medium text-slate-500 hover:text-slate-700">Estimates</a>
                </div>
            </li>
            @if($estimate->client)
                <li>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 flex-shrink-0 text-slate-300" fill="currentColor" viewBox="0 0 20 20"
                            aria-hidden="true">
                            <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                        </svg>
                        <a href="{{ route('clients.show', $estimate->client) }}"
                            class="ml-2 text-sm font-medium text-slate-500 hover:text-slate-700">{{ $estimate->client->name }}</a>
                    </div>
                </li>
            @endif
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-slate-300" fill="currentColor" viewBox="0 0 20 20"
                        aria-hidden="true">
                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    <span class="ml-2 text-sm font-medium text-slate-700"
                        aria-current="page">{{ $estimate->estimate_number }}</span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="sm:flex-auto">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Estimate {{ $estimate->estimate_number }}
            </h1>
            <x-estimate-status-badge :status="$estimate->status" />
            @if($estimate->approval_status && $estimate->approval_status !== 'not_required')
                @php
                    $approvalLabels = [
                        'waiting' => 'Pending Approval',
                        'approved' => 'Internally Approved',
                        'rejected' => 'Rejected',
                        'changes_requested' => 'Changes Requested',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                @if($estimate->approval_status === 'approved') bg-green-50 text-green-700 ring-green-600/20
                                @elseif($estimate->approval_status === 'rejected') bg-red-50 text-red-700 ring-red-600/10
                                @elseif($estimate->approval_status === 'waiting') bg-yellow-50 text-yellow-700 ring-yellow-700/10
                                @elseif($estimate->approval_status === 'changes_requested') bg-amber-50 text-amber-700 ring-amber-600/20
                                @else bg-gray-50 text-gray-600 ring-gray-500/10
                                @endif">
                    {{ $approvalLabels[$estimate->approval_status] ?? ucfirst(str_replace('_', ' ', $estimate->approval_status)) }}
                </span>
            @endif
        </div>

    </div>

    <div class="flex items-center gap-3 bg-white p-2 rounded-lg shadow-sm ring-1 ring-slate-200">
        <!-- Primary Actions -->
        @php
            // Condition: True if we haven't sent it to the client yet, or if they declined and we are fixing it.
            // Also restrict super_admin from illegally editing an "in-flight" document.
            $canEditOrSubmit = in_array($estimate->status, ['draft', 'declined']) ||
                (in_array($estimate->status, ['waiting_approval', 'approved']) && $estimate->client_status !== 'sent');
        @endphp

        @if($canEditOrSubmit)
            @if($estimate->status !== 'waiting_approval' || auth()->user()->hasRole('super_admin'))
                <a href="{{ route('estimates.edit', $estimate) }}"
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                    Edit
                </a>
            @endif

            @if($estimate->approval_status === 'not_required')
                <form action="{{ route('estimates.submit', $estimate) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Submit for Approval
                    </button>
                </form>
            @elseif($estimate->approval_status === 'approved' && $estimate->status !== 'sent')
                <form action="{{ route('estimates.send', $estimate) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Send to Client
                    </button>
                </form>
            @elseif(!$estimate->approvalChain && $estimate->status === 'draft')
                <!-- Fallback to Send if no approval required -->
                <form action="{{ route('estimates.send', $estimate) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Send to Client
                    </button>
                </form>
            @endif

            <!-- Discard Draft -->
            <form action="{{ route('estimates.destroy', $estimate) }}" method="POST" class="inline"
                onsubmit="return confirm('Are you sure you want to discard this draft? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                    Discard
                </button>
            </form>
        @endif

        <!-- Pending Approval Actions (For Approver) -->
        @if(in_array($estimate->approval_status, ['waiting']) && $userApproval)
            @if($estimate->status === 'waiting_approval')
                <!-- Checklist Logic embedded in Approve -->
                <div x-data="{ 
                                        checks: {{ json_encode($estimate->checklistItems->where('is_completed', true)->pluck('approval_checklist_id')->values()) }}, 
                                        requiredCount: {{ $checklists->where('is_required', true)->count() }},
                                        requiredIds: {{ json_encode($checklists->where('is_required', true)->pluck('id')->values()) }},
                                        toggleChecklist(id, checked) {
                                            if (checked) this.checks.push(parseInt(id));
                                            else this.checks = this.checks.filter(c => c !== parseInt(id));
                                            fetch('{{ route('estimates.toggle-checklist', $estimate) }}', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                body: JSON.stringify({ checklist_id: id, completed: checked })
                                            });
                                        },
                                        get canApprove() { return this.requiredIds.every(id => this.checks.includes(id)); }
                                    }" class="flex gap-2 relative">
                    <!-- Approve Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button"
                            class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                            Approve
                        </button>
                        <div x-show="open" @click.outside="open = false"
                            class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                            <h3 class="font-semibold text-gray-900 mb-3">Approval Checklist</h3>
                            <div class="space-y-2 mb-4 max-h-48 overflow-y-auto">
                                @foreach($checklists as $item)
                                    <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox" value="{{ $item->id }}"
                                            @change="toggleChecklist($el.value, $el.checked)"
                                            :checked="checks.includes({{ $item->id }})"
                                            class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600">
                                        <span class="text-left leading-tight">{{ $item->task }} @if($item->is_required)
                                        <span class="text-red-500">*</span> @endif</span>
                                    </label>
                                @endforeach
                            </div>
                            <form action="{{ route('estimates.approve', $estimate) }}" method="POST">
                                @csrf
                                <textarea name="comments" placeholder="Comments..."
                                    class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                                <button type="submit" :disabled="!canApprove"
                                    class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50">Confirm</button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <!-- Simple Approve -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                        class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Approve
                    </button>
                    <div x-show="open" @click.outside="open = false"
                        class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                        <form action="{{ route('estimates.approve', $estimate) }}" method="POST">
                            @csrf
                            <textarea name="comments" placeholder="Comments..."
                                class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                            <button type="submit"
                                class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Confirm
                                Approval</button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Request Changes -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                    class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">
                    Request Changes
                </button>
                <div x-show="open" @click.outside="open = false"
                    class="absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                    <form action="{{ route('estimates.request-changes', $estimate) }}" method="POST">
                        @csrf
                        <p class="text-xs text-slate-500 mb-2">Requesting changes will revert the estimate to draft
                            status for the creator to update.</p>
                        <textarea name="comments" required placeholder="Describe required changes..."
                            class="w-full text-sm rounded border-gray-300 mb-2"></textarea>
                        <button type="submit"
                            class="w-full rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">Confirm
                            Request</button>
                    </form>
                </div>
            </div>

            <!-- Reject -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                    Reject
                </button>
                <div x-show="open" @click.outside="open = false"
                    class="absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                    <form action="{{ route('estimates.reject', $estimate) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reason *</label>
                            <select name="reason_id" required
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-xs">
                                <option value="">Select a reason...</option>
                                @foreach($declineReasons as $reason)
                                    <option value="{{ $reason->id }}">{{ $reason->reason }}</option>
                                @endforeach
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <textarea name="comments" required placeholder="Additional comments..."
                            class="w-full text-sm rounded border-gray-300 mb-2"></textarea>
                        <button type="submit"
                            class="w-full rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Confirm
                            Rejection</button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Copy Link -->
        <div x-data="{ copied: false }">
            <button
                @click="navigator.clipboard.writeText('{{ $estimate->public_url }}'); copied = true; setTimeout(() => copied = false, 2000)"
                type="button"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:text-indigo-600 transition-colors relative"
                title="Copy Public Link">
                <span x-show="!copied" class="flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </span>
                <span x-show="copied" class="flex items-center gap-1 text-green-600" x-cloak>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </button>
        </div>

        <div class="h-6 w-px bg-slate-200"></div>

        <a href="{{ route('estimates.pdf', $estimate) }}"
            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:text-indigo-600 transition-colors">
            <div class="flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                PDF
            </div>
        </a>

        <!-- Manage Dropdown -->
        <div x-data="{ open: false, showVersionModal: false }" class="relative">
            <button @click="open = !open" type="button"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1">
                Manage
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" @click.outside="open = false"
                class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none divide-y divide-slate-100"
                x-cloak>

                <div class="py-1">
                    <a href="{{ route('estimates.print', $estimate) }}" target="_blank"
                        class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Print Estimate</a>
                    <a href="{{ $estimate->public_url }}" target="_blank"
                        class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Public View</a>
                    <a href="{{ route('estimates.analytics', $estimate) }}"
                        class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">View Analytics</a>
                </div>

                <div class="py-1">
                    <!-- Versioning -->
                    <button type="button" @click="showVersionModal = true; open = false"
                        class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Create New Version
                    </button>
                    <form action="{{ route('estimates.copy', $estimate) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Duplicate Estimate
                        </button>
                    </form>
                </div>

            </div>

            <!-- Create Version Modal -->
            <template x-teleport="body">
                <div x-show="showVersionModal" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                    aria-modal="true">
                    <div x-show="showVersionModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

                    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div x-show="showVersionModal" @click.outside="showVersionModal = false"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                class="relative transform overflow-hidden rounded-2xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                                <div>
                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-5">
                                        <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
                                            Create
                                            New Version</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">Are you sure you want to create a new
                                                version of this estimate? This will create a new draft copy (Version
                                                {{ $estimate->version + 1 }}) of the current estimate.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                    <form action="{{ route('estimates.version', $estimate) }}" method="POST"
                                        class="contents">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Create</button>
                                    </form>
                                    <button type="button" @click="showVersionModal = false"
                                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Admin Dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" type="button"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1"
                title="Admin Actions">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>
            <div x-show="open" @click.outside="open = false"
                class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                x-cloak>
                <div class="py-1">
                    <div class="px-4 py-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                        Override</div>
                    @foreach(['draft', 'sent', 'accepted', 'declined', 'expired'] as $status)
                        @if($estimate->status !== $status)
                            <form action="{{ route('estimates.mark-as', [$estimate, $status]) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    Mark as {{ ucfirst($status) }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>