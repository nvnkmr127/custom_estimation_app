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
            <div x-data="{ open: false }" class="relative">
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
                        <form action="{{ route('estimates.version', $estimate) }}" method="POST" onsubmit="return confirm('Create a new version?');">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Create New Version
                            </button>
                        </form>
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
            
             <!-- Client Info -->
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