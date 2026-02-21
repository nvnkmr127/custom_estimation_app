<div x-data="{ showVersionModal: false }">
    @php
        $isApproved = in_array($estimate->status, ['approved', 'accepted']) || $estimate->approval_status === 'approved';
    @endphp


    <!-- Version Warning -->
    @if(!$estimate->is_current_version)
        <div class="mb-6 rounded-md bg-yellow-50 p-4 ring-1 ring-yellow-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Archived Version</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>You are viewing an older version of this estimate.
                            @php $currentVer = $allVersions->firstWhere('is_current_version', true); @endphp
                            @if($currentVer)
                                The <a href="{{ route('estimates.show', $currentVer) }}"
                                    class="font-medium underline hover:text-yellow-600">current version
                                    (v{{ $currentVer->version }})</a> is
                                <strong>{{ ucfirst($currentVer->status) }}</strong>.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Header & Toolbar -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="sm:flex-auto">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Estimate
                    {{ $estimate->estimate_number }}
                </h1>
                <x-estimate-status-badge :status="$estimate->status" />
                @if($estimate->approval_status)
                    <span
                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                                                                                                                                                                                                                                            @if($estimate->approval_status === 'approved') bg-green-50 text-green-700 ring-green-600/20
                                                                                                                                                                                                                                                            @elseif($estimate->approval_status === 'rejected') bg-red-50 text-red-700 ring-red-600/10
                                                                                                                                                                                                                                                            @elseif(in_array($estimate->approval_status, ['submitted', 'pending'])) bg-yellow-50 text-yellow-700 ring-yellow-700/10
                                                                                                                                                                                                                                                                                @else bg-gray-50 text-gray-600 ring-gray-500/10
                                                                                                                                                                                                                                                            @endif">
                        {{ ucfirst($estimate->approval_status) }}
                    </span>
                @endif
            </div>

        </div>

        <div class="flex items-center gap-3 bg-white p-2 rounded-lg shadow-sm ring-1 ring-slate-200">
            <!-- Primary Actions -->
            @if((($estimate->status === 'draft' || $estimate->status === 'declined' || (auth()->user()->hasRole('super_admin'))) && $estimate->is_current_version) && !$isApproved)
                @if($estimate->status !== 'waiting_approval' || auth()->user()->hasRole('super_admin'))

                    <a href="{{ route('estimates.edit', $estimate) }}"
                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        Edit
                    </a>

                @endif

                @if($estimate->approval_status === 'draft' && $estimate->approvalChain)
                    <button type="button" wire:click="submitForApproval"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Submit for Approval
                    </button>
                @elseif($estimate->status === 'draft' && (!$estimate->approvalChain || $estimate->approval_status === 'approved'))
                    <button type="button" wire:click="sendToClient"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Send to Client
                    </button>
                @endif

                <!-- Discard Draft -->
                <button type="button"
                    @click="if(confirm('Are you sure you want to discard this draft? This action cannot be undone.')) { $wire.discard() }"
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                    Discard
                </button>

            @endif

            <!-- Approved Actions -->
            @if($estimate->status === 'approved')
                <button type="button" wire:click="sendToClient"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Send to Client
                </button>
            @endif

            <!-- Pending Approval Actions (For Approver) -->
            @if(in_array($estimate->approval_status, ['submitted', 'pending']) && $userApproval)
                @if($estimate->status === 'waiting_approval')
                    <!-- Checklist Logic embedded in Approve -->
                    <!-- Logic copied from original -->
                    <div x-data="{ 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        checks: {{ json_encode($estimate->checklistItems->where('is_completed', true)->pluck('approval_checklist_id')) }}, 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        requiredCount: {{ $checklists->where('is_required', true)->count() }},
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        requiredIds: {{ json_encode($checklists->where('is_required', true)->pluck('id')) }},
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         toggleChecklist(id, checked) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             if (checked) this.checks.push(parseInt(id));
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             else this.checks = this.checks.filter(c => c !== parseInt(id));
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             $wire.toggleChecklist(id, checked);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         },
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         get canApprove() { return this.requiredIds.every(id => this.checks.includes(id)); }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     }"
                        class="flex gap-2 relative">
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
                                <div x-data="{ comments: '' }">
                                    <textarea x-model="comments" placeholder="Comments..."
                                        class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                                    <button type="button" :disabled="!canApprove" @click="$wire.approve(comments)"
                                        class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50">Confirm</button>
                                </div>
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
                            <div x-data="{ comments: '' }">
                                <textarea x-model="comments" placeholder="Comments..."
                                    class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                                <button type="button" @click="$wire.approve(comments)"
                                    class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Confirm
                                    Approval</button>
                            </div>
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
                        <div x-data="{ comments: '' }">
                            <p class="text-xs text-slate-500 mb-2">Requesting changes will revert the estimate to draft
                                status for the creator to update.</p>
                            <textarea x-model="comments" required placeholder="Describe required changes..."
                                class="w-full text-sm rounded border-gray-300 mb-2"></textarea>
                            <button type="button"
                                @click="if(comments.trim()) { $wire.requestChanges(comments); } else { alert('Please describe required changes'); }"
                                class="w-full rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">Confirm
                                Request</button>
                        </div>
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
                        <div x-data="{ reason_id: '', comments: '' }">
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Reason *</label>
                                <select x-model="reason_id" required
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-xs">
                                    <option value="">Select a reason...</option>
                                    @foreach($declineReasons as $reason)
                                        <option value="{{ $reason->id }}">{{ $reason->reason }}</option>
                                    @endforeach
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <textarea x-model="comments" required placeholder="Additional comments..."
                                class="w-full text-sm rounded border-gray-300 mb-2"></textarea>
                            <button type="button"
                                @click="if(reason_id && comments.trim()) { $wire.reject(reason_id, comments); } else { alert('Please provide a reason and comments'); }"
                                class="w-full rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Confirm
                                Rejection</button>
                        </div>
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

            <a href="{{ route('estimates.portal-preview', $estimate) }}" target="_blank"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:text-indigo-600 transition-colors">
                <div class="flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview
                </div>
            </a>

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
            <div x-data="{ open: false }" class="relative">
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
                        <button type="button" wire:click="duplicate"
                            class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Duplicate Estimate
                        </button>
                    </div>

                    @if($estimate->status === 'approved' || $estimate->status === 'sent')
                        <div class="py-1">
                            @if(!$estimate->perfex_proposal_id)
                                <button type="button" wire:click="perfexSync"
                                    class="block w-full text-left px-4 py-2 text-sm text-indigo-600 hover:bg-slate-50">
                                    Push to Perfex
                                </button>
                            @else
                                <span class="block px-4 py-2 text-xs text-slate-400">Synced to Perfex
                                    (#{{ $estimate->perfex_proposal_id }})</span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Create Version Modal -->
                <template x-teleport="body">
                    <div x-show="showVersionModal" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                        aria-modal="true">
                        <div x-show="showVersionModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div
                                class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div x-show="showVersionModal" @click.outside="showVersionModal = false"
                                    x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
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
                                            <h3 class="text-base font-semibold leading-6 text-gray-900"
                                                id="modal-title">Create New Version</h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">Are you sure you want to create a
                                                    new
                                                    version of this estimate? This will create a new draft copy
                                                    (Version
                                                    {{ $estimate->version + 1 }}) of the current estimate.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                        <button type="button" @click="$wire.createVersion()"
                                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Create</button>
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
                    @if(!$isApproved)
                        <div class="py-1">
                            <div class="px-4 py-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                                Override</div>
                            @foreach(['draft', 'sent', 'accepted', 'declined', 'expired'] as $status)
                                @if($estimate->status !== $status)
                                    <button type="button" wire:click="markAs('{{ $status }}')"
                                        class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                        Mark as {{ ucfirst($status) }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" wire:poll.10s="refreshStats">
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
            <dt class="text-xs font-semibold text-slate-500 uppercase">Grand Total</dt>
            <dd class="mt-1 text-lg font-bold text-slate-900">{{ $estimate->currency }}
                {{ number_format($estimate->grand_total, 2) }}
            </dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
            <dt class="text-xs font-semibold text-slate-500 uppercase">Expiry Date</dt>
            <dd
                class="mt-1 text-lg font-bold {{ $estimate->expiry_date && $estimate->expiry_date < now() ? 'text-red-600' : 'text-slate-900' }}">
                {{ $estimate->expiry_date ? $estimate->expiry_date->format('M d, Y') : 'No Date Set' }}
            </dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
            <dt class="text-xs font-semibold text-slate-500 uppercase">Client Views</dt>
            <dd class="mt-1 text-lg font-bold text-slate-900">{{ $viewCount }}</dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
            <dt class="text-xs font-semibold text-slate-500 uppercase">Last Activity</dt>
            <dd class="mt-1 text-sm font-medium text-slate-700">
                {{ $lastActivity->diffForHumans() }}
            </dd>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left Column: Items & Notes (2/3 width) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Items Table -->
            <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">
                        {{ $estimate->type === 'room_based' ? 'Rooms & Items' : 'Line Items' }}
                    </h2>

                    @if($estimate->type === 'room_based')
                        <div class="space-y-6">
                            @foreach($estimate->sections as $section)
                                <div class="border border-slate-200 rounded-lg overflow-hidden">
                                    <div
                                        class="bg-slate-50 px-4 py-2 border-b border-slate-200 font-medium text-sm text-slate-700">
                                        {{ $section->name }}
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-[800px] w-full divide-y divide-slate-200">
                                            <thead class="bg-slate-50/50">
                                                <tr>
                                                    @if(!$section->is_package)
                                                        <th
                                                            class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16">
                                                            Image
                                                        </th>
                                                    @endif
                                                    @if(!$section->is_package)
                                                        <th
                                                            class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">
                                                            Unit Configuration</th>
                                                    @endif
                                                    <th
                                                        class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                        Item Details</th>
                                                    @if(!$section->is_package)
                                                        <th
                                                            class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                                            Size</th>
                                                    @endif
                                                    <th
                                                        class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                                        Price</th>
                                                    @if(!$section->is_package)
                                                        <th
                                                            class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                                            Quantity</th>
                                                    @endif
                                                    <th
                                                        class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                                        Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 bg-white">
                                                @foreach($section->items as $item)
                                                    <tr class="group hover:bg-slate-50/30 transition-colors">
                                                        @if(!$section->is_package)
                                                            <td class="px-3 py-4 align-middle">
                                                                @if($item->product && $item->product->primary_image_url)
                                                                    <div class="relative h-12 w-12 mx-auto">
                                                                        <img src="{{ $item->product->primary_image_url }}"
                                                                            class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                                                    </div>
                                                                @else
                                                                    <div
                                                                        class="h-12 w-12 bg-slate-50 rounded-lg mx-auto flex items-center justify-center ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                                        <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                        </svg>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        @if(!$section->is_package)
                                                            <td
                                                                class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                                <div class="font-bold text-slate-900">
                                                                    @if($item->unitType) {{ $item->unitType->name }} @endif
                                                                </div>
                                                                <div
                                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                                                    {{ $item->unit_type }}
                                                                </div>
                                                            </td>
                                                        @endif
                                                        <td
                                                            class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                            <div class="min-w-0">
                                                                <div class="font-bold text-slate-900 mb-0.5">{{ $item->name }}</div>
                                                                @if($item->description)
                                                                    <div class="text-xs text-slate-500 leading-relaxed max-w-sm mb-1.5">
                                                                        {{ $item->description }}
                                                                    </div>
                                                                @endif
                                                                @if(!empty($item->options) && is_array($item->options))
                                                                    <div class="flex flex-wrap gap-1.5">
                                                                        @foreach($item->options as $option)
                                                                            <span
                                                                                class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                                {{ $option['name'] }}: {{ $option['value'] }}
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                                @if($item->internal_note)
                                                                    <div
                                                                        class="mt-2 text-[10px] text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 inline-flex items-center gap-1.5 font-medium shadow-xs">
                                                                        <svg class="h-3 w-3 text-amber-500" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                        </svg>
                                                                        <span
                                                                            class="opacity-75 uppercase tracking-wider text-[9px] font-bold">Internal
                                                                            Note:</span> {{ $item->internal_note }}
                                                                    </div>
                                                                @endif
                                                                <!-- Item Comment Button -->
                                                                @php
                                                                    $allComments = $item->comments->flatMap(function ($c) {
                                                                        return collect([$c])->merge($c->replies);
                                                                    })->unique('id')->sortBy('created_at')->values()->map(function ($c) {
                                                                        $c->formatted_date = $c->created_at->format('M j, g:i A');
                                                                        return $c;
                                                                    });
                                                                @endphp
                                                                <button
                                                                    @click="openItemComments({{ $item->id }}, {{ Js::from($item->name) }}, {{ Js::from($allComments) }})"
                                                                    type="button"
                                                                    class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-medium transition-colors
                                                                                                                                                                                                                                                        {{ $allComments->isNotEmpty() ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/10' : 'text-slate-500 hover:bg-slate-100' }}">
                                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                                    </svg>
                                                                    @if($allComments->isNotEmpty())
                                                                        {{ $allComments->count() }}
                                                                        {{ Str::plural('Comment', $allComments->count()) }}
                                                                    @else
                                                                        Comment
                                                                    @endif
                                                                </button>
                                                            </div>
                                                        </td>
                                                        @if(!$section->is_package)
                                                            <td
                                                                class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                                @if($item->length || $item->width || $item->height)
                                                                    <div class="flex flex-col gap-1.5">
                                                                        @if($item->length)
                                                                            <div class="flex items-center gap-2">
                                                                                <span
                                                                                    class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                                                                <span
                                                                                    class="text-xs font-medium text-slate-900">{{ $item->length + 0 }}
                                                                                    ft</span>
                                                                            </div>
                                                                        @endif
                                                                        @if($item->width)
                                                                            <div class="flex items-center gap-2">
                                                                                <span
                                                                                    class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                                                                <span
                                                                                    class="text-xs font-medium text-slate-900">{{ $item->width + 0 }}
                                                                                    ft</span>
                                                                            </div>
                                                                        @endif
                                                                        @if($item->height)
                                                                            <div class="flex items-center gap-2">
                                                                                <span
                                                                                    class="text-[10px] font-bold text-slate-600 uppercase w-3">H</span>
                                                                                <span
                                                                                    class="text-xs font-medium text-slate-900">{{ $item->height + 0 }}
                                                                                    ft</span>
                                                                            </div>
                                                                        @endif

                                                                        @if($item->size > 0)
                                                                            <div class="mt-2 pt-2 border-t border-slate-100">
                                                                                <div
                                                                                    class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-0.5">
                                                                                    {{ ucfirst(str_replace('_', ' ', $item->formula ?: ($item->height > 0 ? 'volume' : 'area'))) }}
                                                                                </div>
                                                                                <div class="text-xs font-bold text-slate-900">
                                                                                    {{ number_format($item->size, 2) }} <span
                                                                                        class="text-slate-500 font-medium">{{ $item->unit_type }}</span>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    <span class="text-xs text-slate-400">-</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        <td
                                                            class="px-3 py-4 text-sm text-right text-slate-600 font-medium align-middle border-b border-slate-100 last:border-0">
                                                            {{ $estimate->currency }} {{ number_format($item->unit_price, 2) }}
                                                        </td>
                                                        @if(!$section->is_package)
                                                            <td
                                                                class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                                <div class="font-bold text-slate-900">{{ $item->quantity }}</div>
                                                            </td>
                                                        @endif
                                                        <td
                                                            class="px-3 py-4 text-sm text-right font-bold text-slate-900 align-middle border-b border-slate-100 last:border-0">
                                                            {{ $estimate->currency }} {{ number_format($item->total, 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-slate-50">
                                                <tr>
                                                    <td colspan="{{ $section->is_package ? '2' : '6' }}"
                                                        class="px-3 py-2 text-xs font-medium text-slate-500 text-right">
                                                        Room Total</td>
                                                    <td class="px-3 py-2 text-xs font-bold text-slate-900 text-right">
                                                        {{ number_format($section->items->sum('total'), 2) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Standard Items Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-[800px] w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50/50">
                                    <tr>
                                        <th
                                            class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16">
                                            Image
                                        </th>
                                        <th
                                            class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">
                                            Unit Configuration</th>
                                        <th
                                            class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            Item Details</th>
                                        <th
                                            class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                            Size</th>
                                        <th
                                            class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                            Price</th>
                                        <th
                                            class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                            Quantity</th>
                                        <th
                                            class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                            Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @foreach($estimate->items as $item)
                                        <tr class="group hover:bg-slate-50/30 transition-colors">
                                            <td class="px-3 py-4 align-middle">
                                                @if($item->product && $item->product->primary_image_url)
                                                    <div class="relative h-12 w-12 mx-auto">
                                                        <img src="{{ $item->product->primary_image_url }}"
                                                            class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                                    </div>
                                                @else
                                                    <div
                                                        class="h-12 w-12 bg-slate-50 rounded-lg mx-auto flex items-center justify-center ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                        <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </td>
                                            <td
                                                class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                <div class="font-bold text-slate-900">
                                                    @if($item->unitType) {{ $item->unitType->name }} @endif
                                                </div>
                                                <div
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                                    {{ $item->unit_type }}
                                                </div>
                                            </td>
                                            <td
                                                class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-900 mb-0.5">{{ $item->name }}</div>
                                                    @if($item->description)
                                                        <div class="text-xs text-slate-500 leading-relaxed max-w-sm mb-1.5">
                                                            {{ $item->description }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($item->options) && is_array($item->options))
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @foreach($item->options as $option)
                                                                <span
                                                                    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                    {{ $option['name'] }}: {{ $option['value'] }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if($item->internal_note)
                                                        <div
                                                            class="mt-2 text-[10px] text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 inline-flex items-center gap-1.5 font-medium shadow-xs">
                                                            <svg class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            <span
                                                                class="opacity-75 uppercase tracking-wider text-[9px] font-bold">Internal
                                                                Note:</span> {{ $item->internal_note }}
                                                        </div>
                                                    @endif
                                                    <!-- Item Comment Button -->
                                                    @php
                                                        $allComments = $item->comments->flatMap(function ($c) {
                                                            return collect([$c])->merge($c->replies);
                                                        })->unique('id')->sortBy('created_at')->values()->map(function ($c) {
                                                            $c->formatted_date = $c->created_at->format('M j, g:i A');
                                                            return $c;
                                                        });
                                                    @endphp
                                                    <button
                                                        @click="openItemComments({{ $item->id }}, {{ Js::from($item->name) }}, {{ Js::from($allComments) }})"
                                                        type="button"
                                                        class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-medium transition-colors
                                                                                                                                                                            {{ $allComments->isNotEmpty() ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/10' : 'text-slate-500 hover:bg-slate-100' }}">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                        </svg>
                                                        @if($allComments->isNotEmpty())
                                                            {{ $allComments->count() }}
                                                            {{ Str::plural('Comment', $allComments->count()) }}
                                                        @else
                                                            Comment
                                                        @endif
                                                    </button>
                                                </div>
                                            </td>
                                            <td
                                                class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                @if($item->length || $item->width || $item->height)
                                                    <div class="flex flex-col gap-1.5">
                                                        @if($item->length)
                                                            <div class="flex items-center gap-2">
                                                                <span
                                                                    class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                                                <span class="text-xs font-medium text-slate-900">{{ $item->length + 0 }}
                                                                    ft</span>
                                                            </div>
                                                        @endif
                                                        @if($item->width)
                                                            <div class="flex items-center gap-2">
                                                                <span
                                                                    class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                                                <span class="text-xs font-medium text-slate-900">{{ $item->width + 0 }}
                                                                    ft</span>
                                                            </div>
                                                        @endif
                                                        @if($item->height)
                                                            <div class="flex items-center gap-2">
                                                                <span
                                                                    class="text-[10px] font-bold text-slate-600 uppercase w-3">H</span>
                                                                <span class="text-xs font-medium text-slate-900">{{ $item->height + 0 }}
                                                                    ft</span>
                                                            </div>
                                                        @endif

                                                        @if($item->size > 0)
                                                            <div class="mt-2 pt-2 border-t border-slate-100">
                                                                <div
                                                                    class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-0.5">
                                                                    {{ ucfirst(str_replace('_', ' ', $item->formula ?: ($item->height > 0 ? 'volume' : 'area'))) }}
                                                                </div>
                                                                <div class="text-xs font-bold text-slate-900">
                                                                    {{ number_format($item->size, 2) }} <span
                                                                        class="text-slate-500 font-medium">{{ $item->unit_type }}</span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-xs text-slate-400">-</span>
                                                @endif
                                            </td>
                                            <td
                                                class="px-3 py-4 text-sm text-right text-slate-600 font-medium align-middle border-b border-slate-100 last:border-0">
                                                {{ $estimate->currency }} {{ number_format($item->unit_price, 2) }}
                                            </td>
                                            <td
                                                class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                <div class="font-bold text-slate-900">{{ $item->quantity }}</div>
                                            </td>
                                            <td
                                                class="px-3 py-4 text-sm text-right font-bold text-slate-900 align-middle border-b border-slate-100 last:border-0">
                                                {{ $estimate->currency }} {{ number_format($item->total, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <!-- Totals -->
                    <div class="mt-8 border-t border-slate-200 pt-6">
                        <dl class="space-y-3 text-sm max-w-xs ml-auto">
                            <div class="flex justify-between text-slate-600">
                                <dt>Subtotal</dt>
                                <dd class="font-medium">{{ $estimate->currency }}
                                    {{ number_format($estimate->subtotal, 2) }}
                                </dd>
                            </div>
                            @if($estimate->total_tax > 0)
                                <div class="flex justify-between text-slate-600">
                                    <dt>Tax</dt>
                                    <dd class="font-medium">{{ $estimate->currency }}
                                        {{ number_format($estimate->total_tax, 2) }}
                                    </dd>
                                </div>
                            @endif
                            @if($estimate->discount_total > 0)
                                <div class="flex justify-between text-red-600">
                                    <dt>Discount</dt>
                                    <dd class="font-medium">- {{ $estimate->currency }}
                                        {{ number_format($estimate->discount_total, 2) }}
                                    </dd>
                                </div>
                            @endif
                            @if($estimate->transportation_charges > 0)
                                <div class="flex justify-between text-slate-600">
                                    <dt>Transportation</dt>
                                    <dd class="font-medium">{{ $estimate->currency }}
                                        {{ number_format($estimate->transportation_charges, 2) }}
                                    </dd>
                                </div>
                            @endif
                            <div
                                class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold text-slate-900">
                                <dt>Grand Total</dt>
                                <dd>{{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($estimate->client_note || $estimate->has_final_terms)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-slate-900 mb-4">Terms & Notes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @if($estimate->client_note)
                            <div>
                                <h4 class="text-sm font-medium text-slate-700 mb-2">Client Note</h4>
                                <p class="text-sm text-slate-500 leading-relaxed">{{ $estimate->client_note }}</p>
                            </div>
                        @endif
                        @if($estimate->has_final_terms)
                            <div>
                                <h4 class="text-sm font-medium text-slate-700 mb-2">Terms & Conditions</h4>
                                <div class="text-sm text-slate-500 leading-relaxed">
                                    {!! $estimate->final_terms_html !!}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Sidebar (1/3 width) -->
        <div class="space-y-6">


            <!-- Approval History -->
            @if($estimate->approvals->isNotEmpty())
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Approval History</h3>
                    <ul class="space-y-4">
                        @foreach($estimate->approvals->sortBy('created_at') as $approval)
                            <li class="relative flex gap-x-4">
                                <div class="absolute left-0 top-0 flex w-6 justify-center -bottom-4">
                                    <div class="w-px bg-gray-200"></div>
                                </div>
                                <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                                    @if($approval->status === 'approved')
                                        <div class="h-1.5 w-1.5 rounded-full bg-green-500 ring-1 ring-green-500"></div>
                                    @elseif($approval->status === 'rejected')
                                        <div class="h-1.5 w-1.5 rounded-full bg-red-500 ring-1 ring-red-500"></div>
                                    @elseif($approval->status === 'changes_requested')
                                        <div class="h-1.5 w-1.5 rounded-full bg-amber-500 ring-1 ring-amber-500"></div>
                                    @else
                                        <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                                    @endif
                                </div>
                                <div class="flex-auto py-0.5 text-xs leading-5 text-gray-500">
                                    <span class="font-medium text-gray-900">{{ $approval->user->name }}</span>
                                    <span class="block">{{ ucfirst(str_replace('_', ' ', $approval->status)) }}</span>
                                    <span class="block text-gray-400">{{ $approval->updated_at->format('M j, g:i A') }}</span>
                                    @if($approval->comments)
                                        <p class="mt-1 text-slate-600 bg-slate-50 p-2 rounded border border-slate-100 italic">
                                            "{{ $approval->comments }}"</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Internal Note -->
            @if($estimate->admin_note)
                <div class="bg-yellow-50 shadow-sm ring-1 ring-yellow-200 sm:rounded-xl px-4 py-5 mb-6">
                    <h3 class="text-sm font-semibold text-yellow-800 mb-2">Internal Note</h3>
                    <div class="text-sm text-yellow-700 whitespace-pre-wrap">{{ $estimate->admin_note }}</div>
                </div>
            @endif



            <!-- Comments Section -->
            <div x-data="{ showCommentsModal: {{ $estimate->comments->where('is_read', false)->isNotEmpty() ? 'true' : 'false' }} }"
                class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-900">Comments</h3>
                    @if($estimate->comments->isNotEmpty())
                        <span
                            class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">
                            {{ $estimate->comments->count() }}
                        </span>
                    @endif
                </div>

                @if($estimate->comments->isEmpty())
                    <p class="text-xs text-slate-500 italic mb-3">No comments yet.</p>
                @else
                    <div class="mb-3">
                        <div class="flex -space-x-1 overflow-hidden">
                            @foreach($estimate->comments->unique('user_id')->take(3) as $comment)
                                @if($comment->isClientComment())
                                    <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600"
                                        title="{{ $comment->client_name }}">
                                        {{ substr($comment->client_name ?: 'C', 0, 1) }}
                                    </div>
                                @else
                                    @if($comment->user && $comment->user->avatar)
                                        <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white"
                                            src="{{ $comment->user->avatar }}" alt="{{ $comment->user->name }}">
                                    @else
                                        <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-600"
                                            title="{{ $comment->user->name ?? 'Staff' }}">
                                            {{ substr($comment->user->name ?? 'S', 0, 1) }}
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                            @if($estimate->comments->unique('user_id')->count() > 3)
                                <div
                                    class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-slate-50 flex items-center justify-center text-[10px] font-medium text-slate-500">
                                    +{{ $estimate->comments->unique('user_id')->count() - 3 }}
                                </div>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2">
                            <span
                                class="font-medium text-slate-700">{{ $estimate->comments->last()->isClientComment() ? ($estimate->comments->last()->client_name ?: 'Client') : 'Staff' }}:</span>
                            {{ $estimate->comments->last()->comment }}
                        </p>
                    </div>
                @endif

                <button @click="showCommentsModal = true" type="button"
                    class="w-full rounded bg-white px-2 py-1.5 text-xs font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                    View Conversation
                </button>

                <!-- Thread Modal -->
                <template x-teleport="body">
                    <div x-show="showCommentsModal" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                        aria-modal="true">
                        <div x-show="showCommentsModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div
                                class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div x-show="showCommentsModal" @click.outside="showCommentsModal = false"
                                    x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg flex flex-col max-h-[85vh]">

                                    <!-- Header -->
                                    <div
                                        class="bg-white px-5 py-4 border-b border-gray-100 flex justify-between items-start sticky top-0 z-10">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900" id="modal-title">
                                                General Comments</h3>
                                            <p class="text-[11px] text-slate-500 mt-0.5">Ask questions about the
                                                estimate</p>
                                        </div>
                                        <button type="button" @click="showCommentsModal = false"
                                            class="text-gray-400 hover:text-gray-600 transition-colors">
                                            <span class="sr-only">Close</span>
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="px-5 py-5 overflow-y-auto flex-1 bg-white space-y-6 min-h-[40vh]"
                                        id="comments-thread-body"
                                        x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                                        @if($estimate->comments->isEmpty())
                                            <div class="text-center py-12">
                                                <div
                                                    class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 mb-3">
                                                    <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                </div>
                                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No comments yet
                                                </h3>
                                                <p class="mt-1 text-xs text-slate-500">Start the conversation with the
                                                    client.</p>
                                            </div>
                                        @else
                                            @foreach($estimate->comments as $comment)
                                                <div
                                                    class="flex {{ $comment->isClientComment() ? 'justify-start' : 'justify-end' }}">
                                                    <div
                                                        class="flex max-w-[85%] {{ $comment->isClientComment() ? 'flex-row' : 'flex-row-reverse' }} items-start gap-3">

                                                        <!-- Avatar & Meta Column -->
                                                        <div class="flex flex-col items-center gap-1.5 pt-1 shrink-0">
                                                            @if($comment->isClientComment())
                                                                <div class="h-8 w-8 rounded-full bg-indigo-50 flex items-center justify-center text-[10px] font-bold text-indigo-600 ring-2 ring-white shadow-sm"
                                                                    title="{{ $comment->client_name }}">
                                                                    {{ substr($comment->client_name ?: 'C', 0, 1) }}
                                                                </div>
                                                            @else
                                                                @if($comment->user && $comment->user->avatar)
                                                                    <img class="h-8 w-8 rounded-full bg-slate-50 ring-2 ring-white shadow-sm"
                                                                        src="{{ $comment->user->avatar }}" alt=""
                                                                        title="{{ $comment->user->name }}">
                                                                @else
                                                                    <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] font-bold text-white ring-2 ring-white shadow-sm"
                                                                        title="{{ $comment->user->name ?? 'Staff' }}">
                                                                        {{ substr($comment->user->name ?? 'S', 0, 1) }}
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>

                                                        <div
                                                            class="flex flex-col {{ $comment->isClientComment() ? 'items-start' : 'items-end' }} min-w-0">
                                                            <!-- Group Meta -->
                                                            <div class="flex items-center gap-2 mb-1.5 px-0.5">
                                                                <span class="text-[10px] font-bold text-slate-900">
                                                                    {{ $comment->isClientComment() ? ($comment->client_name ?: 'Client') : ($comment->user->name ?? 'Staff') }}
                                                                </span>
                                                                <span
                                                                    class="text-[9px] text-slate-400 font-medium tracking-tight">
                                                                    {{ $comment->created_at->format('M j, g:i A') }}
                                                                </span>
                                                                @if(!$comment->isClientComment())
                                                                    <span
                                                                        class="text-[10px] font-extrabold text-indigo-600 uppercase tracking-widest bg-indigo-50 px-1 rounded">You</span>
                                                                @endif
                                                            </div>

                                                            <!-- Item Context Badge (Above Bubble) -->
                                                            @if($comment->commentable_type === 'App\Models\EstimateItem' && $comment->commentable)
                                                                @php
                                                                    $targetItem = $comment->commentable;
                                                                    $targetComments = $targetItem->comments->flatMap(function ($c) {
                                                                        return collect([$c])->merge($c->replies);
                                                                    })->unique('id')->sortBy('created_at')->values()->map(function ($c) {
                                                                        $c->formatted_date = $c->created_at->format('M j, g:i A');
                                                                        return $c;
                                                                    });
                                                                @endphp
                                                                <button type="button"
                                                                    @click="showCommentsModal = false; $nextTick(() => openItemComments({{ $targetItem->id }}, {{ Js::from($targetItem->name) }}, {{ Js::from($targetComments) }}))"
                                                                    class="mb-1.5 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-600 text-white text-[9px] font-black uppercase tracking-widest shadow-sm hover:bg-indigo-700 transition-colors">
                                                                    <span>On Item: {{ $targetItem->name }}</span>
                                                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor" stroke-width="4">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                                    </svg>
                                                                </button>
                                                            @endif

                                                            <!-- Message Bubble -->
                                                            <div class="relative group/bubble max-w-full">
                                                                <div
                                                                    class="{{ $comment->isClientComment() ? 'bg-slate-50 border border-slate-100 text-slate-700 rounded-2xl rounded-tl-none' : 'bg-indigo-600 text-white rounded-2xl rounded-tr-none' }} px-4 py-3 shadow-sm text-[13px] leading-relaxed break-words">
                                                                    <div class="whitespace-pre-wrap">{{ $comment->comment }}
                                                                    </div>
                                                                </div>

                                                                <!-- Status Bubble Footer -->
                                                                @if($comment->status === 'clarified' || ($comment->status === 'pending' && $comment->parent_id === null))
                                                                    <div class="flex items-center gap-2 mt-1.5 px-1">
                                                                        @if($comment->status === 'clarified')
                                                                            <span
                                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">Clarified</span>
                                                                        @else
                                                                            <span
                                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20">Pending</span>
                                                                        @endif

                                                                        @if(auth()->check())
                                                                            <button
                                                                                @click="$wire.toggleCommentStatus({{ $comment->id }}, '{{ $comment->status }}')"
                                                                                type="button"
                                                                                class="text-[9px] font-bold text-slate-400 hover:text-indigo-600 transition-colors underline decoration-dotted underline-offset-2">
                                                                                {{ $comment->status === 'pending' ? 'Mark Clarified' : 'Reopen' }}
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>

                                    <!-- Footer / Reply Form -->
                                    <div class="bg-white px-5 py-4 border-t border-gray-100 w-full">
                                        <div x-data="{ comment: '' }">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 relative">
                                                    <textarea x-model="comment" rows="1"
                                                        @keydown.enter.prevent="if(comment.trim()) { $wire.addComment(comment); comment = ''; }"
                                                        class="block w-full rounded-2xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        placeholder="Type a message..."></textarea>
                                                </div>
                                                <button type="button"
                                                    @click="if(comment.trim()) { $wire.addComment(comment); comment = ''; }"
                                                    class="inline-flex items-center justify-center rounded-full h-10 w-10 bg-indigo-600 text-white shadow-md hover:bg-indigo-700 transition-all transform hover:scale-105 active:scale-95 shrink-0">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Approval Chain -->
            <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Client Details</h3>
                <div class="flex items-start gap-3">
                    <div
                        class="h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold shrink-0">
                        {{ substr($estimate->client->name ?? 'C', 0, 1) }}
                    </div>
                    <div>
                        <div class="font-medium text-slate-900">{{ $estimate->client->name ?? 'Unknown Client' }}
                        </div>
                        <div class="text-sm text-slate-500">{{ $estimate->client->email ?? '' }}</div>
                        <div class="text-sm text-slate-500">{{ $estimate->client->phone ?? '' }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400">
                    Estimate Date: {{ $estimate->estimate_date->format('M d, Y') }}
                </div>
            </div>

            <!-- Created By -->
            <!-- ... existing Created By ... -->

            <!-- Notification: Newer Proposal Available -->

            @if($latestVersion && $latestVersion->version > $estimate->version)
                <div class="bg-blue-50 shadow-sm ring-1 ring-blue-200 sm:rounded-xl px-4 py-3 mb-6">
                    <h3 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-2">Newer Version Available
                    </h3>
                    <p class="text-xs text-blue-700 mb-3">There is a newer draft/proposal
                        (v{{ $latestVersion->version }})
                        available.</p>
                    <a href="{{ route('estimates.show', $latestVersion) }}"
                        class="inline-block rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        View v{{ $latestVersion->version }}
                    </a>
                </div>
            @endif

            <!-- Approval / Make Current (For Creator/Admin) -->
            @if(isset($estimate->parent) && $estimate->is_current_version === false && (auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin'])))
                <div class="bg-yellow-50 shadow-sm ring-1 ring-yellow-200 sm:rounded-xl px-4 py-3 mb-6">
                    <h3 class="text-xs font-bold text-yellow-800 uppercase tracking-wider mb-2">Proposed Changes
                        (v{{ $estimate->version }})</h3>
                    <p class="text-xs text-yellow-700 mb-3">This is a proposed version. Approve it to make it the live
                        version.</p>

                    <button type="button" wire:click="approveVersion"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-green-600 shadow-sm ring-1 ring-inset ring-green-300 hover:bg-green-50">
                        Approve and Publish this Version
                    </button>
                </div>
            @endif

            <!-- Followers -->
            <div x-data="{ showFollowerModal: false }"
                class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-900">Followers</h3>
                    @if(auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin']))
                        <button @click="showFollowerModal = true"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                            + Add
                        </button>
                    @endif
                </div>

                <div class="space-y-3">
                    @foreach($estimate->followers as $follower)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @if($follower->avatar)
                                    <img src="{{ $follower->avatar }}" class="h-6 w-6 rounded-full" alt="">
                                @else
                                    <div
                                        class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600">
                                        {{ substr($follower->name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="text-sm text-slate-700">{{ $follower->name }}</div>
                            </div>

                            @if(auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin']))
                                @if($estimate->created_by !== $follower->id) <!-- Don't remove creator -->
                                    <button type="button"
                                        @click="if(confirm('Remove this follower?')) { $wire.removeFollower({{ $follower->id }}) }"
                                        class="text-slate-400 hover:text-red-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Add Follower Modal (Simplified) -->
                <div x-show="showFollowerModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div x-show="showFollowerModal" @click.away="showFollowerModal = false"
                            class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Add
                                Follower
                            </h3>
                            <div x-data="{ userId: '', permissions: ['view', 'edit'] }">
                                <div class="mb-4">
                                    <label for="follower_user_id"
                                        class="block text-sm font-medium text-gray-700">User</label>
                                    <select id="follower_user_id" x-model="userId" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Select a user...</option>
                                        @foreach(\App\Models\User::whereNotIn('id', $estimate->followers->pluck('id'))->get() as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                                    <div class="flex items-center gap-4">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" value="view" x-model="permissions"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-600">View</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" value="edit" x-model="permissions"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-600">Edit</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                    <button type="button"
                                        @click="if(userId) { $wire.addFollower(userId, permissions); showFollowerModal = false; userId = ''; permissions = ['view', 'edit']; } else { alert('Please select a user'); }"
                                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:col-start-2">Add</button>
                                    <button type="button" @click="showFollowerModal = false"
                                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approval Chain -->
            @if($estimate->approval_chain_id && $estimate->approvalChain)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">Approval Workflow</h3>
                        <a href="#" title="{{ $estimate->approvalChain->name }}"
                            class="text-xs text-indigo-600 hover:text-indigo-500">View Chain</a>
                    </div>

                    <div class="relative pl-3 border-l-2 border-slate-100 space-y-6 my-2">
                        @foreach($estimate->approvalChain->steps as $step)
                            @php $stepApproval = $estimate->approvals()->where('user_id', $step->user_id)->first(); @endphp
                            <div class="relative">
                                <!-- Status Dot -->
                                <div
                                    class="absolute -left-[19px] top-1 h-3 w-3 rounded-full border-2 border-white
                                                                                                                                                                                                                                                                                                                                                                                                                            @if($stepApproval && $stepApproval->status === 'approved') bg-green-500
                                                                                                                                                                                                                                                                                                                                                                                                                            @elseif($stepApproval && $stepApproval->status === 'rejected') bg-red-500
                                                                                                                                                                                                                                                                                                                                                                                                                            @elseif($stepApproval && $stepApproval->status === 'pending') bg-yellow-400
                                                                                                                                                                                                                                                                                                                                                                                                                            @else bg-slate-200
                                                                                                                                                                                                                                                                                                                                                                                                                            @endif">
                                </div>

                                <div class="text-sm font-medium text-slate-900 leading-none">
                                    {{ ucfirst(str_replace('_', ' ', $step->role)) }}
                                </div>
                                <div class="text-xs text-slate-500 mt-1">{{ $step->user->name ?? 'Unassigned' }}</div>

                                @if($stepApproval && $stepApproval->status !== 'pending')
                                    <div class="mt-1 text-xs px-2 py-1 bg-slate-50 rounded inline-block">
                                        <span
                                            class="@if($stepApproval->status === 'approved') text-green-700 @else text-red-700 @endif font-medium">
                                            {{ ucfirst($stepApproval->status) }}
                                        </span>
                                        <span class="text-slate-400"> - {{ $stepApproval->updated_at->format('M d') }}</span>
                                    </div>
                                    @if($stepApproval->comments)
                                        <div class="mt-1 text-xs italic text-slate-500">"{{ $stepApproval->comments }}"</div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Activity Log -->
            <div x-data="{ showActivityModal: false }"
                class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Activity Log</h3>
                    @if($activityLogs->count() > 5)
                        <button @click="showActivityModal = true"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                            View All ({{ $activityLogs->count() }})
                        </button>
                    @endif
                </div>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @forelse($activityLogs->take(5) as $log)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200"
                                            aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span
                                                class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center ring-8 ring-white">
                                                <!-- Icon based on action -->
                                                @if(str_contains($log->action, 'created'))
                                                    <svg class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @elseif(str_contains($log->action, 'updated') || str_contains($log->action, 'edited'))
                                                    <svg class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path
                                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                @elseif(str_contains($log->action, 'deleted'))
                                                    <svg class="h-4 w-4 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @elseif(str_contains($log->action, 'approved'))
                                                    <svg class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-xs text-slate-500">{{ $log->description }} <span
                                                        class="font-medium text-slate-900">by
                                                        {{ $log->user->name ?? 'System' }}</span></p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-xs text-slate-500">
                                                <time
                                                    datetime="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="py-4 text-center text-xs text-slate-400">No activity recorded.</li>
                        @endforelse
                    </ul>

                    @if($activityLogs->count() > 5)
                        <div class="mt-4 text-center border-t border-slate-100 pt-3">
                            <button @click="showActivityModal = true"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                View Previous Activity
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Activity Modal -->
                <div x-show="showActivityModal" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                    aria-modal="true" style="display: none;">
                    <div x-show="showActivityModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div x-show="showActivityModal" @click.outside="showActivityModal = false"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">

                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
                                        Activity History</h3>
                                    <button @click="showActivityModal = false"
                                        class="text-slate-400 hover:text-slate-500">
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm.7-10.3a1 1 0 00-1.4-1.4L6 9.6 2.7 6.3a1 1 0 00-1.4 1.4L4.6 11l-3.3 3.3a1 1 0 001.4 1.4L6 12.4l3.3 3.3a1 1 0 001.4-1.4L7.4 11l3.3-3.3z"
                                                clip-rule="evenodd" fill-opacity="0" />
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="max-h-[60vh] overflow-y-auto pr-2">
                                    <ul role="list" class="-mb-8">
                                        @foreach($activityLogs as $log)
                                            <li>
                                                <div class="relative pb-8">
                                                    @if(!$loop->last)
                                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200"
                                                            aria-hidden="true"></span>
                                                    @endif
                                                    <div class="relative flex space-x-3">
                                                        <div>
                                                            <span
                                                                class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center ring-8 ring-white">
                                                                <!-- Icon based on action -->
                                                                @if(str_contains($log->action, 'created'))
                                                                    <svg class="h-4 w-4 text-green-500" viewBox="0 0 20 20"
                                                                        fill="currentColor">
                                                                        <path fill-rule="evenodd"
                                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @elseif(str_contains($log->action, 'updated') || str_contains($log->action, 'edited'))
                                                                    <svg class="h-4 w-4 text-blue-500" viewBox="0 0 20 20"
                                                                        fill="currentColor">
                                                                        <path
                                                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                                    </svg>
                                                                @elseif(str_contains($log->action, 'deleted'))
                                                                    <svg class="h-4 w-4 text-red-500" viewBox="0 0 20 20"
                                                                        fill="currentColor">
                                                                        <path fill-rule="evenodd"
                                                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @elseif(str_contains($log->action, 'approved'))
                                                                    <svg class="h-4 w-4 text-green-600" viewBox="0 0 20 20"
                                                                        fill="currentColor">
                                                                        <path fill-rule="evenodd"
                                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @else
                                                                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20"
                                                                        fill="currentColor">
                                                                        <path fill-rule="evenodd"
                                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                            <div>
                                                                <p class="text-xs text-slate-500">
                                                                    {{ $log->description }}
                                                                    <span class="font-medium text-slate-900">by
                                                                        {{ $log->user->name ?? 'System' }}</span>
                                                                </p>
                                                            </div>
                                                            <div
                                                                class="whitespace-nowrap text-right text-xs text-slate-500">
                                                                <time
                                                                    datetime="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</time>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="mt-5 sm:mt-6">
                                    <button type="button" @click="showActivityModal = false"
                                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Changes vs Previous -->
            @if(isset($diff) && (!empty($diff['overview']) || !empty($diff['items']['added']) || !empty($diff['items']['removed']) || !empty($diff['items']['modified'])))
                <div x-data="{ showDiffModal: false }" class="mb-6">
                    <!-- Sidebar Summary Card -->
                    <div class="bg-indigo-50 shadow-sm ring-1 ring-indigo-200 sm:rounded-xl px-4 py-5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-indigo-900">Changes vs Previous</h3>
                            <button @click="showDiffModal = true" type="button"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">View
                                Details</button>
                        </div>
                        <p class="text-xs text-indigo-700 mb-3">
                            Comparison with v{{ $estimate->version - 1 }}.
                            @if(!empty($diff['overview'])) <span class="font-medium">{{ count($diff['overview']) }}
                                fields
                            changed.</span> @endif
                            @if(!empty($diff['items']['added'])) <span
                            class="font-medium">{{ count($diff['items']['added']) }} items added.</span> @endif
                            @if(!empty($diff['items']['removed'])) <span
                            class="font-medium">{{ count($diff['items']['removed']) }} items removed.</span> @endif
                            @if(!empty($diff['items']['modified'])) <span
                                class="font-medium">{{ count($diff['items']['modified']) }} items modified.</span>
                            @endif
                        </p>
                        <button @click="showDiffModal = true" type="button"
                            class="w-full rounded bg-indigo-600/10 px-2 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 hover:bg-indigo-600/20">
                            Show All Changes
                        </button>
                    </div>

                    <!-- Diff Modal -->
                    <template x-teleport="body">
                        <div x-show="showDiffModal" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                            aria-modal="true">
                            <div x-show="showDiffModal" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                                <div
                                    class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                    <div x-show="showDiffModal" @click.outside="showDiffModal = false"
                                        x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave="ease-in duration-200"
                                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">

                                        <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                                            <button type="button" @click="showDiffModal = false"
                                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                <span class="sr-only">Close</span>
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                    stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="sm:flex sm:items-start flex-col">
                                            <!-- Sticky Header -->
                                            <div
                                                class="w-full border-b border-slate-200 pb-4 mb-4 sticky top-0 bg-white z-10 pt-2">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h3 class="text-lg font-bold leading-6 text-slate-900"
                                                            id="modal-title">
                                                            Version Analysis
                                                        </h3>
                                                        <p class="text-sm text-slate-500 mt-1">
                                                            Comparing
                                                            <span
                                                                class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">v{{ $estimate->version }}</span>
                                                            &rarr;
                                                            <span
                                                                class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">v{{ $estimate->version - 1 }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="w-full space-y-6 text-sm max-h-[70vh] overflow-y-auto pr-2 pb-6">

                                                <!-- Net Financial Impact Card -->
                                                @php
                                                    $oldTotal = $diff['overview']['grand_total']['old'] ?? (isset($previousVersion) ? $previousVersion->grand_total : 0);
                                                    $oldTotalVal = 0;
                                                    foreach ($diff['overview'] as $o) {
                                                        if ($o['label'] === 'Grand Total')
                                                            $oldTotalVal = $o['old'];
                                                    }
                                                    if ($oldTotalVal == 0 && isset($previousVersion))
                                                        $oldTotalVal = $previousVersion->grand_total;

                                                    $newTotal = $estimate->grand_total;
                                                    $netChange = $newTotal - $oldTotalVal;
                                                    $percentChange = $oldTotalVal > 0 ? ($netChange / $oldTotalVal) * 100 : 0;
                                                    $isPositive = $netChange > 0;
                                                    $isNegative = $netChange < 0;
                                                @endphp

                                                <div
                                                    class="relative overflow-hidden rounded-xl border {{ $isPositive ? 'border-red-200 bg-red-50' : ($isNegative ? 'border-green-200 bg-green-50' : 'border-slate-200 bg-slate-50') }} p-4 shadow-sm">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <p
                                                                class="text-sm font-medium text-slate-500 uppercase tracking-wider">
                                                                Net Financial Impact</p>
                                                            <div class="mt-1 flex items-baseline gap-2">
                                                                <span
                                                                    class="text-3xl font-bold tracking-tight {{ $isPositive ? 'text-red-700' : ($isNegative ? 'text-green-700' : 'text-slate-900') }} font-mono">
                                                                    {{ $netChange > 0 ? '+' : '' }}{{ number_format($netChange, 2) }}
                                                                </span>
                                                                <span
                                                                    class="rounded-full px-2 py-0.5 text-sm font-semibold {{ $isPositive ? 'bg-red-100 text-red-800' : ($isNegative ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-800') }}">
                                                                    {{ number_format(abs($percentChange), 1) }}%
                                                                </span>
                                                            </div>
                                                            <p class="text-xs text-slate-500 mt-1">From
                                                                {{ number_format($oldTotalVal, 2) }} to
                                                                {{ number_format($newTotal, 2) }}
                                                            </p>
                                                        </div>
                                                        <div
                                                            class="rounded-full p-3 {{ $isPositive ? 'bg-red-100' : ($isNegative ? 'bg-green-100' : 'bg-slate-200') }}">
                                                            @if($isPositive)
                                                                <svg class="h-6 w-6 text-red-600" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="1.5"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                                                                </svg>
                                                            @elseif($isNegative)
                                                                <svg class="h-6 w-6 text-green-600" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="1.5"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 0111.818 8.818" />
                                                                </svg>
                                                            @else
                                                                <svg class="h-6 w-6 text-slate-600" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="1.5"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M5 12h14" />
                                                                </svg>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Overview Changes -->
                                                @if(!empty($diff['overview']))
                                                    <div>
                                                        <div
                                                            class="flex items-center gap-2 mb-3 pb-1 border-b border-slate-100">
                                                            <svg class="h-5 w-5 text-slate-400"
                                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                                fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <h4 class="font-semibold text-slate-900">General Updates</h4>
                                                        </div>
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                            @foreach($diff['overview'] as $change)
                                                                <div
                                                                    class="flex flex-col justify-between bg-white rounded-lg p-3 border border-slate-200 shadow-sm hover:border-indigo-300 transition-colors">
                                                                    <span
                                                                        class="text-xs font-semibold text-slate-500 uppercase">{{ $change['label'] }}</span>
                                                                    <div class="flex items-center gap-2 mt-2">
                                                                        <span
                                                                            class="text-slate-400 line-through text-xs">{{ $change['is_currency'] ? number_format($change['old'], 2) : $change['old'] }}</span>
                                                                        <svg class="h-3 w-3 text-slate-300" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                                        </svg>
                                                                        <span
                                                                            class="font-bold text-slate-900 {{ $change['label'] === 'Grand Total' ? 'text-indigo-600' : '' }}">{{ $change['is_currency'] ? number_format($change['new'], 2) : $change['new'] }}</span>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Added Items -->
                                                @if(!empty($diff['items']['added']))
                                                    <div>
                                                        <div
                                                            class="flex items-center gap-2 mb-3 pb-1 border-b border-green-100">
                                                            <div
                                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100">
                                                                <svg class="h-4 w-4 text-green-600" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="2.5"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M12 4.5v15m7.5-7.5h-15" />
                                                                </svg>
                                                            </div>
                                                            <h4 class="font-semibold text-slate-900">Added Items</h4>
                                                            <span
                                                                class="ml-auto bg-green-100 text-green-700 py-0.5 px-2 rounded-full text-xs font-medium">{{ count($diff['items']['added'], COUNT_RECURSIVE) - count($diff['items']['added']) }}
                                                                items</span>
                                                        </div>
                                                        @foreach($diff['items']['added'] as $section => $items)
                                                            <div class="mb-4 last:mb-0">
                                                                <h5
                                                                    class="text-xs font-bold text-slate-400 uppercase mb-2 pl-1 tracking-wider">
                                                                    {{ $section }}
                                                                </h5>
                                                                <div class="space-y-2">
                                                                    @foreach($items as $item)
                                                                        <div
                                                                            class="group flex items-start justify-between rounded-lg border border-green-100 bg-green-50/50 p-3 transition hover:bg-green-50">
                                                                            <div class="pr-4">
                                                                                <p class="font-medium text-slate-900">{{ $item->name }}
                                                                                </p>
                                                                                <div class="mt-1 text-xs text-green-700">
                                                                                    <span
                                                                                        class="font-mono font-medium">{{ $item->quantity }}</span>
                                                                                    &times; <span
                                                                                        class="font-mono">{{ number_format($item->unit_price, 2) }}</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="text-right">
                                                                                <p class="font-bold text-green-700 font-mono">
                                                                                    +{{ number_format($item->total, 2) }}</p>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Modified Items -->
                                                @if(!empty($diff['items']['modified']))
                                                    <div>
                                                        <div
                                                            class="flex items-center gap-2 mb-3 pb-1 border-b border-amber-100">
                                                            <div
                                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100">
                                                                <svg class="h-4 w-4 text-amber-600" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="2.5"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                                                </svg>
                                                            </div>
                                                            <h4 class="font-semibold text-slate-900">Modified Items</h4>
                                                            <span
                                                                class="ml-auto bg-amber-100 text-amber-700 py-0.5 px-2 rounded-full text-xs font-medium">{{ count($diff['items']['modified'], COUNT_RECURSIVE) - count($diff['items']['modified']) }}
                                                                items</span>
                                                        </div>
                                                        @foreach($diff['items']['modified'] as $section => $modItems)
                                                            <div class="mb-4 last:mb-0">
                                                                <h5
                                                                    class="text-xs font-bold text-slate-400 uppercase mb-2 pl-1 tracking-wider">
                                                                    {{ $section }}
                                                                </h5>
                                                                <div class="space-y-3">
                                                                    @foreach($modItems as $mod)
                                                                        <div
                                                                            class="rounded-lg border border-amber-200 bg-white p-3 shadow-sm">
                                                                            <div
                                                                                class="font-medium text-slate-900 border-b border-slate-100 pb-2 mb-2 flex items-center justify-between">
                                                                                {{ $mod['item']->name }}
                                                                                <span
                                                                                    class="text-[10px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">EDITED</span>
                                                                            </div>
                                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                                @foreach($mod['changes'] as $change)
                                                                                    <div
                                                                                        class="rounded bg-slate-50 p-2 text-xs border border-slate-100">
                                                                                        <span
                                                                                            class="block text-slate-500 font-medium mb-1">{{ $change['field'] }}</span>
                                                                                        <div class="flex items-center gap-1.5 font-mono">
                                                                                            <span
                                                                                                class="line-through text-slate-400 decoration-slate-400/50">{{ is_numeric($change['old']) ? number_format($change['old'], 2) : $change['old'] }}</span>
                                                                                            <svg class="h-3 w-3 text-slate-300" fill="none"
                                                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                                                <path stroke-linecap="round"
                                                                                                    stroke-linejoin="round" stroke-width="2"
                                                                                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                                                            </svg>
                                                                                            <span
                                                                                                class="font-bold text-amber-700">{{ is_numeric($change['new']) ? number_format($change['new'], 2) : $change['new'] }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Removed Items -->
                                                @if(!empty($diff['items']['removed']))
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-3 pb-1 border-b border-red-100">
                                                            <div
                                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100">
                                                                <svg class="h-4 w-4 text-red-600" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="2.5"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                                </svg>
                                                            </div>
                                                            <h4 class="font-semibold text-slate-900">Removed Items</h4>
                                                            <span
                                                                class="ml-auto bg-red-100 text-red-700 py-0.5 px-2 rounded-full text-xs font-medium">{{ count($diff['items']['removed'], COUNT_RECURSIVE) - count($diff['items']['removed']) }}
                                                                items</span>
                                                        </div>
                                                        @foreach($diff['items']['removed'] as $section => $items)
                                                            <div class="mb-4 last:mb-0">
                                                                <h5
                                                                    class="text-xs font-bold text-slate-400 uppercase mb-2 pl-1 tracking-wider">
                                                                    {{ $section }}
                                                                </h5>
                                                                <div class="space-y-2">
                                                                    @foreach($items as $item)
                                                                        <div
                                                                            class="flex items-center justify-between rounded-lg border border-red-100 bg-red-50/30 p-3 opacity-75 grayscale-[0.3]">
                                                                            <div class="pr-4">
                                                                                <p
                                                                                    class="font-medium text-slate-900 line-through decoration-red-500/50">
                                                                                    {{ $item->name }}
                                                                                </p>
                                                                                <div class="mt-1 text-xs text-slate-500">
                                                                                    {{ $item->quantity }} &times;
                                                                                    {{ number_format($item->unit_price, 2) }}
                                                                                </div>
                                                                            </div>
                                                                            <div class="text-right">
                                                                                <p class="font-bold text-red-700 font-mono">
                                                                                    -{{ number_format($item->total, 2) }}</p>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                            </div>
                                        </div>



                                        <div class="mt-5 sm:mt-6">
                                            <button type="button" @click="showDiffModal = false"
                                                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif

            <!-- History / Versions -->
            @if($allVersions->count() > 1)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Version History</h3>
                    <div class="space-y-2">
                        @foreach($allVersions as $ver)
                            <a href="{{ route('estimates.show', $ver) }}"
                                class="flex items-center justify-between p-2 rounded-md hover:bg-slate-50 {{ $ver->id === $estimate->id ? 'bg-indigo-50 ring-1 ring-indigo-200' : '' }}">
                                <div class="text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-slate-900">v{{ $ver->version }}</span>
                                        <span class="text-xs text-slate-500">by {{ $ver->creator->name ?? 'System' }}</span>
                                    </div>
                                    <span
                                        class="text-slate-500 text-xs block">{{ $ver->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                @if($ver->is_current_version) <span
                                    class="text-[10px] uppercase font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded">Current</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Signature -->
            @if($estimate->signed_at)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Signed by Client</h3>
                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-lg">
                        <img src="{{ $estimate->signature }}" alt="Signature" class="h-12 mb-2 mix-blend-multiply">
                        <div class="text-xs text-slate-500">
                            <div>Signed: {{ $estimate->signed_at->format('M d, Y h:i A') }}</div>
                            <div>IP: {{ $estimate->signer_ip }}</div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Item Comments Modal -->
    <div x-data="{
                isOpen: false,
                itemId: null,
                itemName: '',
                comments: [],
                newComment: '',
                isSubmitting: false,
                init() {
                    window.addEventListener('open-item-comments', (e) => {
                        this.itemId = e.detail.id;
                        this.itemName = e.detail.name;
                        this.comments = Array.isArray(e.detail.comments) ? e.detail.comments : Object.values(e.detail.comments);
                        this.isOpen = true;
                        this.$nextTick(() => { const el = document.getElementById('item-comments-body'); if (el) el.scrollTop = el.scrollHeight; });
                    });
                },
                async submit() {
                    if (!this.newComment.trim()) return;
                    this.isSubmitting = true;

                    const commentText = this.newComment;

                    // Optimistic Update
                    this.comments.push({
                        id: 'temp-' + Date.now(),
                        comment: commentText,
                        type: 'internal',
                        created_at: new Date().toISOString(),
                        formatted_date: 'Just now',
                        user: { name: 'You' },
                        status: 'pending'
                    });

                    this.newComment = '';
                    this.$nextTick(() => { const el = document.getElementById('item-comments-body'); if (el) el.scrollTop = el.scrollHeight; });

                    try {
                        await $wire.addItemComment(this.itemId, commentText);
                        // Convert Livewire's fresh data to our format if we could access it, 
                        // but for now the optimistic update holds until page reload or re-open
                    } catch (e) {
                        console.error(e);
                        alert('Failed to post comment');
                        this.comments.pop(); // Remove optimistic comment on failure
                        this.newComment = commentText; // Restore text
                    } finally {
                        this.isSubmitting = false;
                    }
                },
                async toggleStatus(commentId, currentStatus) {
                    try {
                        // Find local comment and update status optimistically
                        const comment = this.comments.find(c => c.id === commentId);
                        if (comment) {
                            comment.status = currentStatus === 'pending' ? 'clarified' : 'pending';
                        }
                        await $wire.toggleCommentStatus(commentId, currentStatus);
                    } catch (e) { console.error(e); }
                }
            }" x-show="isOpen" @keydown.escape.window="isOpen = false" class="relative z-50"
        aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">

        <!-- Backdrop -->
        <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="isOpen" @click.outside="isOpen = false" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-xl flex flex-col max-h-[90vh]">

                    <!-- Header -->
                    <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Item Discussion</h3>
                            <p class="text-xs text-slate-500" x-text="itemName"></p>
                        </div>
                        <button type="button" @click="isOpen = false" class="text-slate-400 hover:text-slate-600">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Thread -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50/50 min-h-[40vh] scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100"
                        id="item-comments-body">
                        <template x-if="comments.length === 0">
                            <div class="flex flex-col items-center justify-center h-full text-slate-400 py-10">
                                <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                    </path>
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-slate-900">No comments yet</h3>
                                <p class="mt-1 text-sm text-slate-500">Start the conversation!</p>
                            </div>
                        </template>

                        <template x-for="comment in comments" :key="comment.id">
                            <!-- Admin View: Client on Left, Internal on Right -->
                            <div class="flex" :class="comment.type === 'client' ? 'justify-start' : 'justify-end'">
                                <div class="max-w-[85%]">
                                    <div class="flex items-end gap-2 mb-1"
                                        :class="comment.type === 'client' ? '' : 'justify-end'">
                                        <div x-show="comment.type === 'client'" class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-700"
                                                x-text="comment.client_name || comment.commenter_name || 'Client'"></span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-medium"
                                            x-text="comment.formatted_date"></span>
                                        <div x-show="comment.type !== 'client'" class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-700"
                                                x-text="comment.user ? comment.user.name : 'You'"></span>
                                        </div>
                                    </div>

                                    <div class="p-3 rounded-2xl shadow-sm text-sm leading-relaxed relative group"
                                        :class="comment.type === 'client' ? 'bg-white border border-slate-200 text-slate-700 rounded-tl-none' : 'bg-indigo-600 text-white rounded-tr-none'">
                                        <p class="whitespace-pre-wrap" x-text="comment.comment"></p>

                                        <!-- Actions (Status Toggle) for Admin -->
                                        <div class="mt-2 pt-2 border-t flex justify-end"
                                            :class="comment.type === 'client' ? 'border-slate-100' : 'border-indigo-500/30'">
                                            <button @click="toggleStatus(comment.id, comment.status)" type="button"
                                                class="text-[10px] underline hover:no-underline font-medium transition-colors"
                                                :class="comment.type === 'client' ? 'text-slate-400 hover:text-indigo-600' : 'text-indigo-200 hover:text-white'">
                                                <span
                                                    x-text="comment.status === 'pending' ? 'Mark Resolved' : 'Reopen'"></span>
                                                <span x-show="comment.status === 'clarified'"
                                                    class="ml-1 opacity-70">(Resolved)</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="bg-white px-4 py-4 border-t border-slate-100 w-full">
                        <div class="flex gap-2">
                            <textarea x-model="newComment" rows="1"
                                class="flex-1 rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 resize-none py-3 text-sm shadow-sm"
                                placeholder="Type a message..."></textarea>
                            <button type="button" @click="submit" :disabled="isSubmitting"
                                class="p-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 shadow-md transition-colors flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <svg x-show="isSubmitting" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Version Creation Modal -->
    <div x-show="showVersionModal" style="display: none;" class="relative z-50" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div x-show="showVersionModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showVersionModal" @click.outside="showVersionModal = false"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Create New
                                Version</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to create a new version of this
                                    estimate? The current version will be archived.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                        <button type="button" wire:click="createVersion" @click="showVersionModal = false"
                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:col-start-2">Create
                            Version</button>
                        <button type="button" @click="showVersionModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
@push('scripts')
    <script>
        // Defensive initialization to prevent ReferenceErrors
        window.estimate = @json($estimate);
        window.totals = {
            subtotal: {{ $estimate->subtotal ?? 0 }},
            totalTax: {{ $estimate->total_tax ?? 0 }},
            discount: {{ $estimate->discount_total ?? 0 }},
            grandTotal: {{ $estimate->grand_total ?? 0 }} 
                                                                                                                                                                                                    };

        const defaults = {
            hasCustomItems: () => false,
            isSubmitting: false,
            couponValid: false,
            couponMessage: '',
            couponInput: '',
            appliedCouponCode: '',
            productPicker: { isOpen: false, search: '', sectionIndex: null },
            internalNoteModal: { isOpen: false, activeItem: null },
            configModal: { isOpen: false, product: null, options: {}, basePrice: 0 }
        };

        for (const [key, value] of Object.entries(defaults)) {
            if (typeof window[key] === 'undefined') {
                window[key] = value;
            }
        }



        window.openItemComments = function (id, name, comments) {
            window.dispatchEvent(new CustomEvent('open-item-comments', { detail: { id, name, comments } }));
        };
    </script>
@endpush

</div>