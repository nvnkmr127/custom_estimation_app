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
            @if($estimate->status === 'draft')
                <a href="{{ route('estimates.edit', $estimate) }}" 
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                    Edit
                </a>
                
                @if($estimate->approval_chain_id && $estimate->approval_status === 'draft')
                    <form action="{{ route('estimates.submit', $estimate) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Submit for Approval
                        </button>
                    </form>
                @else
                    <form action="{{ route('estimates.send', $estimate) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Send to Client
                        </button>
                    </form>
                @endif
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
                                        <thead class="bg-white">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-500">Item</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-slate-500">Price</th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-slate-500">Qty</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-slate-500">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            @foreach($section->items as $item)
                                                <tr>
                                                    <td class="px-3 py-3 text-sm text-slate-900">
                                                        <div class="flex items-start gap-3">
                                                            @if($item->product && $item->product->images->isNotEmpty())
                                                                <img src="{{ $item->product->images->first()->image_path }}" class="h-10 w-10 object-cover rounded text-xs ring-1 ring-slate-200">
                                                            @else
                                                                <div class="h-10 w-10 bg-slate-100 rounded flex items-center justify-center ring-1 ring-slate-200">
                                                                    <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <div class="font-medium">{{ $item->name }}</div>
                                                                @if($item->length && $item->width)
                                                                    <div class="text-xs text-indigo-600">Dims: {{ $item->length + 0 }} &times; {{ $item->width + 0 }}</div>
                                                                @endif
                                                                @if($item->description)
                                                                    <div class="text-xs text-slate-500 truncate max-w-xs">{{ $item->description }}</div>
                                                                @endif
                                                                @if($item->internal_note)
                                                                    <div class="mt-1 text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 inline-block">
                                                                        <span class="font-semibold">Note:</span> {{ $item->internal_note }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-3 text-sm text-right text-slate-500">{{ number_format($item->unit_price, 2) }}</td>
                                                    <td class="px-3 py-3 text-sm text-center text-slate-500">{{ $item->quantity }} {{ $item->unit_type }}</td>
                                                    <td class="px-3 py-3 text-sm text-right font-medium text-slate-900">{{ number_format($item->total, 2) }}</td>
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
                             <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">Item</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase">Price</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase">Qty</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($estimate->items as $item)
                                    <tr>
                                        <td class="px-3 py-3 text-sm text-slate-900">
                                             <div class="flex items-start gap-3">
                                                 @if($item->product && $item->product->images->isNotEmpty())
                                                    <img src="{{ $item->product->images->first()->image_path }}" class="h-10 w-10 object-cover rounded text-xs ring-1 ring-slate-200">
                                                @else
                                                    <div class="h-10 w-10 bg-slate-100 rounded flex items-center justify-center ring-1 ring-slate-200">
                                                        <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="font-medium">{{ $item->name }}</div>
                                                    @if($item->description)
                                                        <div class="text-xs text-slate-500">{{ $item->description }}</div>
                                                    @endif
                                                    @if($item->internal_note)
                                                        <div class="mt-1 text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 inline-block">
                                                            <span class="font-semibold">Note:</span> {{ $item->internal_note }}
                                                        </div>
                                                    @endif
                                                </div>
                                             </div>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-right text-slate-500">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="px-3 py-3 text-sm text-center text-slate-500">{{ $item->quantity }} {{ $item->unit_type }}</td>
                                        <td class="px-3 py-3 text-sm text-right font-medium text-slate-900">{{ number_format($item->total, 2) }}</td>
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
                            <div class="flex justify-between text-slate-600">
                                <dt>Tax</dt>
                                <dd class="font-medium">{{ $estimate->currency }} {{ number_format($estimate->total_tax, 2) }}</dd>
                            </div>
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
</x-app-layout>