<x-app-layout>
    <!-- Version Warning -->
    @if(!$estimate->is_current_version)
        <div class="mb-6 rounded-md bg-yellow-50 p-4 ring-1 ring-yellow-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Archived Version</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>You are viewing an older version of this estimate. 
                        @php $currentVer = $allVersions->firstWhere('is_current_version', true); @endphp
                        @if($currentVer)
                            The <a href="{{ route('estimates.show', $currentVer) }}" class="font-medium underline hover:text-yellow-600">current version (v{{ $currentVer->version }})</a> is <strong>{{ ucfirst($currentVer->status) }}</strong>.
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
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Estimate {{ $estimate->estimate_number }}</h1>
                <x-estimate-status-badge :status="$estimate->status" />
                @if($estimate->approval_status)
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                    @if($estimate->approval_status === 'approved') bg-green-50 text-green-700 ring-green-600/20
                    @elseif($estimate->approval_status === 'rejected') bg-red-50 text-red-700 ring-red-600/10
                    @elseif($estimate->approval_status === 'submitted') bg-yellow-50 text-yellow-700 ring-yellow-700/10
                    @else bg-gray-50 text-gray-600 ring-gray-500/10
                    @endif">
                        {{ ucfirst($estimate->approval_status) }}
                    </span>
                @endif
            </div>

        </div>

        <div class="flex items-center gap-3 bg-white p-2 rounded-lg shadow-sm ring-1 ring-slate-200">
            <!-- Primary Actions -->
            @if($estimate->status === 'draft' || $estimate->status === 'declined' || (auth()->user()->hasRole('super_admin')))
                @if($estimate->status !== 'waiting_approval' || auth()->user()->hasRole('super_admin'))
                    <a href="{{ route('estimates.edit', $estimate) }}" 
                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        Edit
                    </a>
                @endif
                
                @if($estimate->approval_chain_id && $estimate->approval_status === 'draft')
                    <form action="{{ route('estimates.submit', $estimate) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Submit for Approval
                        </button>
                    </form>
                @elseif($estimate->status === 'draft')
                    <form action="{{ route('estimates.send', $estimate) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Send to Client
                        </button>
                    </form>
                @endif

                <!-- Discard Draft -->
                <form action="{{ route('estimates.destroy', $estimate) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to discard this draft? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                        Discard
                    </button>
                </form>
            @endif

            <!-- Pending Approval Actions (For Approver) -->
            @if($estimate->approval_status === 'submitted' && $userApproval)
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
                            <button @click="open = !open" type="button" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                Approve
                            </button>
                            <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                                <h3 class="font-semibold text-gray-900 mb-3">Approval Checklist</h3>
                                <div class="space-y-2 mb-4 max-h-48 overflow-y-auto">
                                    @foreach($checklists as $item)
                                        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" value="{{ $item->id }}" @change="toggleChecklist($el.value, $el.checked)" :checked="checks.includes({{ $item->id }})" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600">
                                            <span class="text-left leading-tight">{{ $item->task }} @if($item->is_required) <span class="text-red-500">*</span> @endif</span>
                                        </label>
                                    @endforeach
                                </div>
                                <form action="{{ route('estimates.approve', $estimate) }}" method="POST">
                                    @csrf
                                    <textarea name="comments" placeholder="Comments..." class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                                    <button type="submit" :disabled="!canApprove" class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50">Confirm</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Simple Approve -->
                     <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                            Approve
                        </button>
                        <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                            <form action="{{ route('estimates.approve', $estimate) }}" method="POST">
                                @csrf
                                <textarea name="comments" placeholder="Comments..." class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                                <button type="submit" class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Confirm Approval</button>
                            </form>
                        </div>
                    </div>
                @endif
                
                <!-- Reject -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        Reject
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                        <form action="{{ route('estimates.reject', $estimate) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Reason *</label>
                                <select name="reason_id" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-xs">
                                    <option value="">Select a reason...</option>
                                    @foreach($declineReasons as $reason)
                                        <option value="{{ $reason->id }}">{{ $reason->reason }}</option>
                                    @endforeach
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <textarea name="comments" required placeholder="Additional comments..." class="w-full text-sm rounded border-gray-300 mb-2"></textarea>
                            <button type="submit" class="w-full rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Confirm Rejection</button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Copy Link -->
            <div x-data="{ copied: false }">
                <button @click="navigator.clipboard.writeText('{{ $estimate->public_url }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    type="button" 
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:text-indigo-600 transition-colors relative"
                    title="Copy Public Link">
                    <span x-show="!copied" class="flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
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

            <a href="{{ route('estimates.pdf', $estimate) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:text-indigo-600 transition-colors">
                <div class="flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    PDF
                </div>
            </a>

            <!-- Manage Dropdown -->
            <div x-data="{ open: false, showVersionModal: false }" class="relative">
                <button @click="open = !open" type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1">
                    Manage
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open = false" 
                    class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none divide-y divide-slate-100" x-cloak>
                    
                    <div class="py-1">
                        <a href="{{ route('estimates.print', $estimate) }}" target="_blank" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Print Estimate</a>
                        <a href="{{ $estimate->public_url }}" target="_blank" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Public View</a>
                        <a href="{{ route('estimates.analytics', $estimate) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">View Analytics</a>
                    </div>

                    <div class="py-1">
                        <!-- Versioning -->
                        <button type="button" 
                                @click="showVersionModal = true; open = false"
                                class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Create New Version
                        </button>
                         <form action="{{ route('estimates.copy', $estimate) }}" method="POST">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Duplicate Estimate
                            </button>
                        </form>
                    </div>

                    @if($estimate->status === 'approved' || $estimate->status === 'sent')
                        <div class="py-1">
                            @if(!$estimate->perfex_proposal_id)
                                <form action="{{ route('estimates.sync', $estimate) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-indigo-600 hover:bg-slate-50">
                                        Push to Perfex
                                    </button>
                                </form>
                            @else
                                <span class="block px-4 py-2 text-xs text-slate-400">Synced to Perfex (#{{ $estimate->perfex_proposal_id }})</span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Create Version Modal -->
                <template x-teleport="body">
                    <div x-show="showVersionModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div x-show="showVersionModal" 
                             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div x-show="showVersionModal" 
                                     @click.outside="showVersionModal = false"
                                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                                    <div>
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-5">
                                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Create New Version</h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">Are you sure you want to create a new version of this estimate? This will create a new draft copy (Version {{ $estimate->version + 1 }}) of the current estimate.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                        <form action="{{ route('estimates.version', $estimate) }}" method="POST" class="contents">
                                            @csrf
                                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Create</button>
                                        </form>
                                        <button type="button" @click="showVersionModal = false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Admin Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1" title="Admin Actions">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open = false" 
                    class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" x-cloak>
                    <div class="py-1">
                        <div class="px-4 py-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status Override</div>
                        @foreach(['draft', 'sent', 'accepted', 'declined', 'expired'] as $status)
                            @if($estimate->status !== $status)
                                <form action="{{ route('estimates.mark-as', [$estimate, $status]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
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

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
            <dt class="text-xs font-semibold text-slate-500 uppercase">Grand Total</dt>
            <dd class="mt-1 text-lg font-bold text-slate-900">{{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}</dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
            <dt class="text-xs font-semibold text-slate-500 uppercase">Expiry Date</dt>
            <dd class="mt-1 text-lg font-bold {{ $estimate->expiry_date && $estimate->expiry_date < now() ? 'text-red-600' : 'text-slate-900' }}">
                {{ $estimate->expiry_date ? $estimate->expiry_date->format('M d, Y') : 'No Date Set' }}
            </dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
            <dt class="text-xs font-semibold text-slate-500 uppercase">Client Views</dt>
            <dd class="mt-1 text-lg font-bold text-slate-900">{{ $estimate->view_count }}</dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-slate-200">
             <dt class="text-xs font-semibold text-slate-500 uppercase">Last Activity</dt>
             <dd class="mt-1 text-sm font-medium text-slate-700">
                 {{ $estimate->updated_at->diffForHumans() }}
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
                                    <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 font-medium text-sm text-slate-700">
                                        {{ $section->name }}
                                    </div>
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50/50">
                                            <tr>
                                                <th class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Item Details</th>
                                                <th class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">Price</th>
                                                <th class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">Quantity</th>
                                                <th class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            @foreach($section->items as $item)
                                                <tr class="group hover:bg-slate-50/30 transition-colors">
                                                    <td class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                        <div class="flex items-start gap-4">
                                                            @if($item->product && $item->product->images->isNotEmpty())
                                                                <div class="relative h-12 w-12 flex-shrink-0">
                                                                    <img src="{{ $item->product->images->first()->image_path }}" class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                                                </div>
                                                            @else
                                                                <div class="h-12 w-12 bg-slate-50 rounded-lg flex items-center justify-center flex-shrink-0 ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                                    <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                                </div>
                                                            @endif
                                                            <div class="min-w-0">
                                                                <div class="font-bold text-slate-900 mb-0.5">{{ $item->name }}</div>
                                                                @if($item->length && $item->width)
                                                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-indigo-50 text-[10px] font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 mb-1.5">
                                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m16-10v10M4 12h16" /></svg>
                                                                        {{ $item->length + 0 }} &times; {{ $item->width + 0 }} ft
                                                                    </div>
                                                                @endif
                                                                @if($item->description)
                                                                    <div class="text-xs text-slate-500 leading-relaxed max-w-sm mb-1.5">{{ $item->description }}</div>
                                                                @endif
                                                                @if(!empty($item->options) && is_array($item->options))
                                                                    <div class="flex flex-wrap gap-1.5">
                                                                        @foreach($item->options as $option)
                                                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                                {{ $option['name'] }}: {{ $option['value'] }}
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                                @if($item->internal_note)
                                                                    <div class="mt-2 text-[10px] text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 inline-flex items-center gap-1.5 font-medium shadow-xs">
                                                                        <svg class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                                        <span class="opacity-75 uppercase tracking-wider text-[9px] font-bold">Internal Note:</span> {{ $item->internal_note }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-right text-slate-600 font-medium align-middle border-b border-slate-100 last:border-0">
                                                        {{ $estimate->currency }} {{ number_format($item->unit_price, 2) }}
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                        <div class="font-bold text-slate-900">{{ $item->quantity }}</div>
                                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                                            @if($item->unitType) {{ $item->unitType->name }} @endif
                                                            {{ $item->unit_type }}
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-right font-bold text-slate-900 align-middle border-b border-slate-100 last:border-0">
                                                        {{ $estimate->currency }} {{ number_format($item->total, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-slate-50">
                                            <tr>
                                                <td colspan="3" class="px-3 py-2 text-xs font-medium text-slate-500 text-right">Room Total</td>
                                                <td class="px-3 py-2 text-xs font-bold text-slate-900 text-right">{{ number_format($section->items->sum('total'), 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Standard Items Table -->
                        <table class="min-w-full divide-y divide-slate-200">
                             <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Item Details</th>
                                    <th class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">Price</th>
                                    <th class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32 text-center">Quantity</th>
                                    <th class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($estimate->items as $item)
                                    <tr class="group hover:bg-slate-50/30 transition-colors">
                                        <td class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                            <div class="flex items-start gap-4">
                                                @if($item->product && $item->product->images->isNotEmpty())
                                                    <div class="relative h-12 w-12 flex-shrink-0">
                                                        <img src="{{ $item->product->images->first()->image_path }}" class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                                    </div>
                                                @else
                                                    <div class="h-12 w-12 bg-slate-50 rounded-lg flex items-center justify-center flex-shrink-0 ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                        <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    </div>
                                                @endif
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-900 mb-0.5">{{ $item->name }}</div>
                                                    @if($item->length && $item->width)
                                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-indigo-50 text-[10px] font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 mb-1.5">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m16-10v10M4 12h16" /></svg>
                                                            {{ $item->length + 0 }} &times; {{ $item->width + 0 }} ft
                                                        </div>
                                                    @endif
                                                    @if($item->description)
                                                        <div class="text-xs text-slate-500 leading-relaxed max-w-sm mb-1.5">{{ $item->description }}</div>
                                                    @endif
                                                    @if(!empty($item->options) && is_array($item->options))
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @foreach($item->options as $option)
                                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                    {{ $option['name'] }}: {{ $option['value'] }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if($item->internal_note)
                                                        <div class="mt-2 text-[10px] text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 inline-flex items-center gap-1.5 font-medium shadow-xs">
                                                            <svg class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                            <span class="opacity-75 uppercase tracking-wider text-[9px] font-bold">Internal Note:</span> {{ $item->internal_note }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-right text-slate-600 font-medium align-middle border-b border-slate-100 last:border-0">
                                            {{ $estimate->currency }} {{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                            <div class="font-bold text-slate-900">{{ $item->quantity }}</div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                                @if($item->unitType) {{ $item->unitType->name }} @endif
                                                {{ $item->unit_type }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-right font-bold text-slate-900 align-middle border-b border-slate-100 last:border-0">
                                            {{ $estimate->currency }} {{ number_format($item->total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <!-- Totals -->
                    <div class="mt-8 border-t border-slate-200 pt-6">
                         <dl class="space-y-3 text-sm max-w-xs ml-auto">
                            <div class="flex justify-between text-slate-600">
                                <dt>Subtotal</dt>
                                <dd class="font-medium">{{ $estimate->currency }} {{ number_format($estimate->subtotal, 2) }}</dd>
                            </div>
                            @if($estimate->total_tax > 0)
                                <div class="flex justify-between text-slate-600">
                                    <dt>Tax</dt>
                                    <dd class="font-medium">{{ $estimate->currency }} {{ number_format($estimate->total_tax, 2) }}</dd>
                                </div>
                            @endif
                            @if($estimate->discount_total > 0)
                                <div class="flex justify-between text-red-600">
                                    <dt>Discount</dt>
                                    <dd class="font-medium">- {{ $estimate->currency }} {{ number_format($estimate->discount_total, 2) }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold text-slate-900">
                                <dt>Grand Total</dt>
                                <dd>{{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($estimate->client_note || $estimate->terms)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-slate-900 mb-4">Terms & Notes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @if($estimate->client_note)
                            <div>
                                <h4 class="text-sm font-medium text-slate-700 mb-2">Client Note</h4>
                                <p class="text-sm text-slate-500 leading-relaxed">{{ $estimate->client_note }}</p>
                            </div>
                        @endif
                        @if($estimate->terms)
                            <div>
                                <h4 class="text-sm font-medium text-slate-700 mb-2">Terms & Conditions</h4>
                                <p class="text-sm text-slate-500 leading-relaxed whitespace-pre-wrap">{{ $estimate->terms }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Sidebar (1/3 width) -->
        <div class="space-y-6">
            
             <!-- Internal Note -->
            @if($estimate->admin_note)
                <div class="bg-yellow-50 shadow-sm ring-1 ring-yellow-200 sm:rounded-xl px-4 py-5 mb-6">
                    <h3 class="text-sm font-semibold text-yellow-800 mb-2">Internal Note</h3>
                    <div class="text-sm text-yellow-700 whitespace-pre-wrap">{{ $estimate->admin_note }}</div>
                </div>
            @endif
            

            
            <!-- Comments Section -->
             <div x-data="{ showCommentsModal: {{ $estimate->comments->where('is_read', false)->isNotEmpty() ? 'true' : 'false' }} }" class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-900">Comments</h3>
                    @if($estimate->comments->isNotEmpty())
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">
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
                                    <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600" title="{{ $comment->client_name }}">
                                        {{ substr($comment->client_name ?: 'C', 0, 1) }}
                                    </div>
                                @else
                                    @if($comment->user && $comment->user->avatar)
                                         <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white" src="{{ $comment->user->avatar }}" alt="{{ $comment->user->name }}">
                                    @else
                                        <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-600" title="{{ $comment->user->name ?? 'Staff' }}">
                                            {{ substr($comment->user->name ?? 'S', 0, 1) }}
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                            @if($estimate->comments->unique('user_id')->count() > 3)
                                <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-slate-50 flex items-center justify-center text-[10px] font-medium text-slate-500">
                                    +{{ $estimate->comments->unique('user_id')->count() - 3 }}
                                </div>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2">
                            <span class="font-medium text-slate-700">{{ $estimate->comments->last()->isClientComment() ? ($estimate->comments->last()->client_name ?: 'Client') : 'Staff' }}:</span> 
                            {{ $estimate->comments->last()->comment }}
                        </p>
                    </div>
                @endif
                
                <button @click="showCommentsModal = true" type="button" class="w-full rounded bg-white px-2 py-1.5 text-xs font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                    View Conversation
                </button>

                <!-- Thread Modal -->
                <template x-teleport="body">
                    <div x-show="showCommentsModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div x-show="showCommentsModal" 
                             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div x-show="showCommentsModal" 
                                     @click.outside="showCommentsModal = false"
                                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg flex flex-col max-h-[85vh]">
                                    
                                    <!-- Header -->
                                    <div class="bg-white px-4 py-3 border-b border-gray-200 flex justify-between items-center sticky top-0 z-10">
                                        <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Discussion Thread</h3>
                                        <button type="button" @click="showCommentsModal = false" class="text-gray-400 hover:text-gray-500">
                                            <span class="sr-only">Close</span>
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Body (Scrollable) -->
                                    <div class="px-4 py-4 overflow-y-auto flex-1 bg-slate-50 space-y-4 min-h-[40vh]" id="comments-thread-body" x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                                        @if($estimate->comments->isEmpty())
                                            <div class="text-center py-8">
                                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 mb-3">
                                                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                </div>
                                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No comments yet</h3>
                                                <p class="mt-1 text-sm text-gray-500">Start the conversation with the client.</p>
                                            </div>
                                        @else
                                            @foreach($estimate->comments as $comment)
                                                <div class="flex {{ $comment->isClientComment() ? 'justify-start' : 'justify-end' }}">
                                                    <div class="flex max-w-[85%] {{ $comment->isClientComment() ? 'flex-row' : 'flex-row-reverse' }} items-end gap-2">
                                                        <!-- Avatar -->
                                                        <div class="flex-shrink-0">
                                                            @if($comment->isClientComment())
                                                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-600 ring-1 ring-white shadow-sm" title="{{ $comment->client_name }}">
                                                                    {{ substr($comment->client_name ?: 'C', 0, 1) }}
                                                                </div>
                                                            @else
                                                                @if($comment->user && $comment->user->avatar)
                                                                    <img class="h-8 w-8 rounded-full bg-gray-50 ring-1 ring-white shadow-sm" src="{{ $comment->user->avatar }}" alt="" title="{{ $comment->user->name }}">
                                                                @else
                                                                    <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold text-white ring-1 ring-white shadow-sm" title="{{ $comment->user->name ?? 'Staff' }}">
                                                                        {{ substr($comment->user->name ?? 'S', 0, 1) }}
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>

                                                        <!-- Message Bubble -->
                                                        <div class="{{ $comment->isClientComment() ? 'bg-white rounded-tl-2xl rounded-tr-2xl rounded-br-2xl text-slate-700' : 'bg-indigo-600 rounded-tl-2xl rounded-tr-2xl rounded-bl-2xl text-white' }} p-3 shadow-sm ring-1 ring-black/5 text-sm">
                                                            <div class="font-semibold text-[11px] mb-1 opacity-90 {{ $comment->isClientComment() ? 'text-slate-500' : 'text-indigo-100' }}">
                                                                {{ $comment->isClientComment() ? ($comment->client_name ?: 'Client') : ($comment->user->name ?? 'Staff') }}
                                                            </div>
                                                            <div class="whitespace-pre-wrap leading-relaxed">{{ $comment->comment }}</div>
                                                            <div class="text-[10px] mt-1 text-right opacity-70 {{ $comment->isClientComment() ? 'text-slate-400' : 'text-indigo-200' }}">
                                                                {{ $comment->created_at->format('M j, g:i A') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>

                                    <!-- Footer / Reply Form -->
                                    <div class="bg-white px-4 py-4 border-t border-gray-200 w-full">
                                         <form action="{{ route('estimates.reply', $estimate) }}" method="POST">
                                            @csrf
                                            <div class="relative">
                                                <label for="markdown-comment" class="sr-only">Add your comment</label>
                                                <textarea id="markdown-comment" name="comment" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Write a reply..."></textarea>
                                                <div class="mt-2 flex justify-end">
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                                        Send Reply
                                                    </button>
                                                </div>
                                            </div>
                                         </form>
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
                     <div class="h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold shrink-0">
                         {{ substr($estimate->client->name ?? 'C', 0, 1) }}
                    </div>
                    <div>
                        <div class="font-medium text-slate-900">{{ $estimate->client->name ?? 'Unknown Client' }}</div>
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
                     <h3 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-2">Newer Version Available</h3>
                     <p class="text-xs text-blue-700 mb-3">There is a newer draft/proposal (v{{ $latestVersion->version }}) available.</p>
                     <a href="{{ route('estimates.show', $latestVersion) }}" class="inline-block rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                         View v{{ $latestVersion->version }}
                     </a>
                </div>
            @endif

            <!-- Approval / Make Current (For Creator/Admin) -->
            @if(isset($estimate->parent) && $estimate->is_current_version === false && (auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin'])))
                <div class="bg-yellow-50 shadow-sm ring-1 ring-yellow-200 sm:rounded-xl px-4 py-3 mb-6">
                     <h3 class="text-xs font-bold text-yellow-800 uppercase tracking-wider mb-2">Proposed Changes (v{{ $estimate->version }})</h3>
                     <p class="text-xs text-yellow-700 mb-3">This is a proposed version. Approve it to make it the live version.</p>
                     
                     <form action="{{ route('estimates.approve-version', $estimate) }}" method="POST">
                         @csrf
                         <button type="submit" class="w-full rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600">
                             Approve & Make Live
                         </button>
                     </form>
                </div>
            @endif

            <!-- Followers -->
            <div x-data="{ showFollowerModal: false }" class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-900">Followers</h3>
                    @if(auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin']))
                        <button @click="showFollowerModal = true" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
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
                                    <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600">
                                        {{ substr($follower->name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="text-sm text-slate-700">{{ $follower->name }}</div>
                            </div>
                            
                            @if(auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin']))
                                @if($estimate->created_by !== $follower->id) <!-- Don't remove creator -->
                                    <form action="{{ route('estimates.followers.remove', [$estimate, $follower]) }}" method="POST" onsubmit="return confirm('Remove this follower?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-slate-400 hover:text-red-500">&times;</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Add Follower Modal (Simplified) -->
                <div x-show="showFollowerModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                         <div x-show="showFollowerModal" @click.away="showFollowerModal = false" class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                             <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Add Follower</h3>
                             <form action="{{ route('estimates.followers.add', $estimate) }}" method="POST" class="mt-4">
                                 @csrf
                                 <div class="mb-4">
                                     <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                                     <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                         @foreach($potentialFollowers as $u)
                                             <option value="{{ $u->id }}">{{ $u->name }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="mb-4">
                                     <div class="relative flex items-start">
                                         <div class="flex h-6 items-center">
                                             <input id="permission_edit" aria-describedby="permission_edit-description" name="permissions[]" value="edit" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                         </div>
                                         <div class="ml-3 text-sm leading-6">
                                             <label for="permission_edit" class="font-medium text-gray-900">Allow Editing</label>
                                             <p id="permission_edit-description" class="text-gray-500">User can edit this estimate (edits will create a new version for approval).</p>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                     <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:col-start-2">Add</button>
                                     <button type="button" @click="showFollowerModal = false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">Cancel</button>
                                 </div>
                             </form>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Approval Chain -->
            @if($estimate->approval_chain_id && $estimate->approvalChain)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">Approval Workflow</h3>
                        <a href="#" title="{{ $estimate->approvalChain->name }}" class="text-xs text-indigo-600 hover:text-indigo-500">View Chain</a>
                    </div>
                    
                    <div class="relative pl-3 border-l-2 border-slate-100 space-y-6 my-2">
                        @foreach($estimate->approvalChain->steps as $step)
                            @php $stepApproval = $estimate->approvals()->where('user_id', $step->user_id)->first(); @endphp
                            <div class="relative">
                                <!-- Status Dot -->
                                <div class="absolute -left-[19px] top-1 h-3 w-3 rounded-full border-2 border-white
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
                                        <span class="@if($stepApproval->status === 'approved') text-green-700 @else text-red-700 @endif font-medium">
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
            <div x-data="{ showActivityModal: false }" class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Activity Log</h3>
                    @if($activityLogs->count() > 5)
                        <button @click="showActivityModal = true" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
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
                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center ring-8 ring-white">
                                                <!-- Icon based on action -->
                                                @if(str_contains($log->action, 'created'))
                                                    <svg class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" /></svg>
                                                @elseif(str_contains($log->action, 'updated') || str_contains($log->action, 'edited'))
                                                    <svg class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                                                @elseif(str_contains($log->action, 'deleted'))
                                                    <svg class="h-4 w-4 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                @elseif(str_contains($log->action, 'approved'))
                                                    <svg class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                @else
                                                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-xs text-slate-500">{{ $log->description }} <span class="font-medium text-slate-900">by {{ $log->user->name ?? 'System' }}</span></p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-xs text-slate-500">
                                                <time datetime="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</time>
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
                            <button @click="showActivityModal = true" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                View Previous Activity
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Activity Modal -->
                <div x-show="showActivityModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
                    <div x-show="showActivityModal" 
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div x-show="showActivityModal" 
                                 @click.outside="showActivityModal = false"
                                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                 class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                                
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Activity History</h3>
                                    <button @click="showActivityModal = false" class="text-slate-400 hover:text-slate-500">
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.7-10.3a1 1 0 00-1.4-1.4L6 9.6 2.7 6.3a1 1 0 00-1.4 1.4L4.6 11l-3.3 3.3a1 1 0 001.4 1.4L6 12.4l3.3 3.3a1 1 0 001.4-1.4L7.4 11l3.3-3.3z" clip-rule="evenodd" fill-opacity="0" /><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                </div>
                                
                                <div class="max-h-[60vh] overflow-y-auto pr-2">
                                     <ul role="list" class="-mb-8">
                                        @foreach($activityLogs as $log)
                                            <li>
                                                <div class="relative pb-8">
                                                    @if(!$loop->last)
                                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                                    @endif
                                                    <div class="relative flex space-x-3">
                                                        <div>
                                                            <span class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center ring-8 ring-white">
                                                                <!-- Icon based on action -->
                                                                @if(str_contains($log->action, 'created'))
                                                                    <svg class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" /></svg>
                                                                @elseif(str_contains($log->action, 'updated') || str_contains($log->action, 'edited'))
                                                                    <svg class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                                                                @elseif(str_contains($log->action, 'deleted'))
                                                                    <svg class="h-4 w-4 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                                @elseif(str_contains($log->action, 'approved'))
                                                                    <svg class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                                @else
                                                                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                            <div>
                                                                <p class="text-xs text-slate-500">{{ $log->description }} <span class="font-medium text-slate-900">by {{ $log->user->name ?? 'System' }}</span></p>
                                                            </div>
                                                            <div class="whitespace-nowrap text-right text-xs text-slate-500">
                                                                <time datetime="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</time>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                
                                <div class="mt-5 sm:mt-6">
                                    <button type="button" @click="showActivityModal = false" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Close</button>
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
                             <button @click="showDiffModal = true" type="button" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">View Details</button>
                        </div>
                        <p class="text-xs text-indigo-700 mb-3">
                            Comparison with v{{ $estimate->version - 1 }}.
                            @if(!empty($diff['overview'])) <span class="font-medium">{{ count($diff['overview']) }} fields changed.</span> @endif
                            @if(!empty($diff['items']['added'])) <span class="font-medium">{{ count($diff['items']['added']) }} items added.</span> @endif
                            @if(!empty($diff['items']['removed'])) <span class="font-medium">{{ count($diff['items']['removed']) }} items removed.</span> @endif
                            @if(!empty($diff['items']['modified'])) <span class="font-medium">{{ count($diff['items']['modified']) }} items modified.</span> @endif
                        </p>
                        <button @click="showDiffModal = true" type="button" class="w-full rounded bg-indigo-600/10 px-2 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 hover:bg-indigo-600/20">
                            Show All Changes
                        </button>
                    </div>

                    <!-- Diff Modal -->
                    <template x-teleport="body">
                        <div x-show="showDiffModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <div x-show="showDiffModal" 
                                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                    <div x-show="showDiffModal" 
                                         @click.outside="showDiffModal = false"
                                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                         class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                                        
                                        <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                                            <button type="button" @click="showDiffModal = false" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                <span class="sr-only">Close</span>
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="sm:flex sm:items-start">
                                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                                <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Version Changes</h3>
                                                <p class="text-sm text-gray-500 mb-4">Comparing Version {{ $estimate->version }} with Version {{ $estimate->version - 1 }}</p>
                                                
                                                <div class="space-y-6 text-sm max-h-[70vh] overflow-y-auto pr-2">
                                                    <!-- Overview Changes -->
                                                    @if(!empty($diff['overview']))
                                                        <div>
                                                            <h4 class="font-medium text-slate-900 mb-2 border-b pb-1">General Updates</h4>
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                                @foreach($diff['overview'] as $change)
                                                                    <div class="bg-slate-50 rounded p-3 text-slate-900 text-xs shadow-sm ring-1 ring-slate-100">
                                                                        <span class="font-semibold block mb-1">{{ $change['label'] }}</span>
                                                                        <div class="flex items-center gap-2 text-slate-500">
                                                                            <span class="line-through">{{ $change['is_currency'] ? number_format($change['old'], 2) : $change['old'] }}</span>
                                                                            <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                                                            <span class="font-bold text-slate-900">{{ $change['is_currency'] ? number_format($change['new'], 2) : $change['new'] }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <!-- Added Items -->
                                                    @if(!empty($diff['items']['added']))
                                                        <div>
                                                            <h4 class="font-medium text-green-700 mb-2 border-b border-green-200 pb-1">Added Items</h4>
                                                            <ul class="space-y-2">
                                                                @foreach($diff['items']['added'] as $item)
                                                                    <li class="bg-green-50 rounded p-3 ring-1 ring-green-100">
                                                                        <div class="flex justify-between items-start">
                                                                            <div>
                                                                                <div class="font-medium text-green-900">{{ $item->name }}</div>
                                                                                <div class="text-xs text-green-700">{{ $item->section->name ?? 'General' }}</div>
                                                                            </div>
                                                                            <div class="text-right text-xs">
                                                                                <div class="font-semibold text-green-900">{{ number_format($item->total, 2) }}</div>
                                                                                <div class="text-green-700">{{ $item->quantity }} x {{ number_format($item->unit_price, 2) }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <!-- Modified Items -->
                                                    @if(!empty($diff['items']['modified']))
                                                        <div>
                                                            <h4 class="font-medium text-amber-700 mb-2 border-b border-amber-200 pb-1">Modified Items</h4>
                                                            <ul class="space-y-2">
                                                                @foreach($diff['items']['modified'] as $mod)
                                                                    <li class="bg-amber-50 rounded p-3 ring-1 ring-amber-100">
                                                                        <div class="font-medium text-amber-900 mb-2">{{ $mod['item']->name }} <span class="text-xs font-normal text-amber-700">({{ $mod['item']->section->name ?? 'General' }})</span></div>
                                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                            @foreach($mod['changes'] as $change)
                                                                                <div class="bg-white/50 rounded p-1.5 text-xs">
                                                                                    <span class="text-amber-800 font-medium">{{ $change['field'] }}:</span>
                                                                                    <div class="flex items-center gap-1 mt-0.5">
                                                                                        <span class="line-through text-slate-500">{{ $change['old'] }}</span>
                                                                                        <span>&rarr;</span>
                                                                                        <span class="font-bold text-slate-900">{{ $change['new'] }}</span>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <!-- Removed Items -->
                                                    @if(!empty($diff['items']['removed']))
                                                        <div>
                                                            <h4 class="font-medium text-red-700 mb-2 border-b border-red-200 pb-1">Removed Items</h4>
                                                            <ul class="space-y-2">
                                                                @foreach($diff['items']['removed'] as $item)
                                                                    <li class="bg-red-50 rounded p-3 ring-1 ring-red-100 opacity-75">
                                                                         <div class="flex justify-between items-start">
                                                                            <div>
                                                                                <div class="font-medium text-red-900 line-through">{{ $item->name }}</div>
                                                                                <div class="text-xs text-red-700">{{ $item->section->name ?? 'General' }}</div>
                                                                            </div>
                                                                            <div class="text-right text-xs">
                                                                                <div class="font-semibold text-red-900 line-through">{{ number_format($item->total, 2) }}</div>
                                                                                <div class="text-red-700">{{ $item->quantity }} x {{ number_format($item->unit_price, 2) }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-5 sm:mt-6">
                                            <button type="button" @click="showDiffModal = false" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Close</button>
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
                            <a href="{{ route('estimates.show', $ver) }}" class="flex items-center justify-between p-2 rounded-md hover:bg-slate-50 {{ $ver->id === $estimate->id ? 'bg-indigo-50 ring-1 ring-indigo-200' : '' }}">
                                <div class="text-sm">
                                    <span class="font-medium text-slate-900">v{{ $ver->version }}</span>
                                    <span class="text-slate-500 text-xs ml-2">{{ $ver->estimate_date->format('M d') }}</span>
                                </div>
                                @if($ver->is_current_version) <span class="text-[10px] uppercase font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded">Current</span> @endif
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
    </script>
    @endpush
</x-app-layout>