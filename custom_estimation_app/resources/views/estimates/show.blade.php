<x-app-layout>
    <div class="sm:flex sm:items-center mb-8">
        <div class="sm:flex-auto">
            <h1 class="text-base font-semibold leading-6 text-gray-900">Estimate {{ $estimate->estimate_number }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $estimate->title }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-2">
            @if($estimate->approval_status === 'draft' && $estimate->approval_chain_id)
                <form action="{{ route('estimates.submit', $estimate) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="rounded-md bg-yellow-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                        Submit for Approval
                    </button>
                </form>
            @elseif($estimate->status === 'draft')
                <form action="{{ route('estimates.submit', $estimate) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="rounded-md bg-yellow-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                        Submit
                    </button>
                </form>
            @endif

            @if($estimate->approval_status === 'submitted')
                @php
                    $userApproval = $estimate->approvals()
                        ->where('user_id', auth()->id())
                        ->where('status', 'pending')
                        ->first();
                @endphp
                
                @if($userApproval)
                    <div class="flex gap-2">
                        <!-- Approve -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button" class="rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                Approve
                            </button>
                            
                            <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                                <h3 class="font-semibold text-gray-900 mb-3 block text-left">Approve Estimate</h3>
                                <form action="{{ route('estimates.approve', $estimate) }}" method="POST">
                                    @csrf
                                    <textarea name="comments" placeholder="Comments (optional)..." class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                                    <button type="submit" 
                                        class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                        Confirm Approval
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Reject -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button" class="rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                Reject
                            </button>
                            <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4">
                                <h3 class="font-semibold text-gray-900 mb-2 text-left">Reject Estimate</h3>
                                <form action="{{ route('estimates.reject', $estimate) }}" method="POST">
                                    @csrf
                                    <textarea name="comments" required placeholder="Reason for rejection..." class="w-full text-sm rounded border-gray-300 mb-2"></textarea>
                                    <button type="submit" class="w-full rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                        Confirm Rejection
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if($estimate->status === 'waiting_approval')
                <div x-data="{ 
                    checks: [], 
                    requiredCount: {{ $checklists->where('is_required', true)->count() }},
                    showRejectInfo: false
                }" class="flex gap-2 relative">
                    
                    <!-- Approve with Checklist -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button" class="rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                            Approve
                        </button>
                        
                        <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4 text-left">
                            <h3 class="font-semibold text-gray-900 mb-3 block text-left">Approval Checklist</h3>
                            <div class="space-y-2 mb-4">
                                @foreach($checklists as $item)
                                    <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox" value="{{ $item->id }}" 
                                            @if($item->is_required) x-model="checks" @endif
                                            class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                        <span class="text-left">
                                            {{ $item->task }}
                                            @if($item->is_required) <span class="text-red-500">*</span> @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            
                            <form action="{{ route('estimates.approve', $estimate) }}" method="POST">
                                @csrf
                                <textarea name="comments" placeholder="Comments..." class="w-full text-xs rounded border-gray-300 mb-2"></textarea>
                                <button type="submit" :disabled="checks.length < requiredCount" 
                                    class="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Confirm
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Reject -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button" class="rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            Reject
                        </button>
                         <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-20 p-4">
                            <h3 class="font-semibold text-gray-900 mb-2 text-left">Reject Estimate</h3>
                            <form action="{{ route('estimates.reject', $estimate) }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1 text-left">Reason *</label>
                                    <select name="reason_id" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-xs sm:leading-6">
                                        <option value="">Select a reason...</option>
                                        @foreach($declineReasons as $reason)
                                            <option value="{{ $reason->id }}">{{ $reason->reason }}</option>
                                        @endforeach
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <textarea name="comments" required placeholder="Additional comments..." class="w-full text-sm rounded border-gray-300 mb-2"></textarea>
                                <button type="submit" class="w-full rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                    Confirm
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Sync Button -->
            @if($estimate->status === 'approved' || $estimate->status === 'sent')
                @if($estimate->perfex_proposal_id)
                     <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                        Synced (ID: {{ $estimate->perfex_proposal_id }})
                    </span>
                @else
                    <form action="{{ route('estimates.sync', $estimate) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Push to Perfex
                        </button>
                    </form>
                @endif
            @endif

            <!-- Versioning Controls -->
            <form action="{{ route('estimates.version', $estimate) }}" method="POST" class="inline"
                onsubmit="return confirm('Create a new version? The current version will be archived.');">
                @csrf
                <button type="submit"
                    class="rounded-md bg-indigo-50 px-3 py-2 text-center text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 ring-1 ring-inset ring-indigo-200">
                    Create Version
                </button>
            </form>

            @if($estimate->versions->count() > 0 || $estimate->parent)
                <div x-data="{ open: false }" class="relative inline-block text-left">
                    <button @click="open = !open" type="button"
                        class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        History (v{{ $estimate->version }})
                        <svg class="-mr-1 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                        class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <div class="py-1">
                            @php
                                // Get root parent to find all siblings
                                $root = $estimate->parent ?? $estimate;
                                // Collect all versions: root + its children
                                // Note: We need a way to get *all* versions of this 'family'.
                                // For simplicity, let's just use the current logic which might be limited if we are in a child.
                                // Ideal: $allVersions = Estimate::where('id', $root->id)->orWhere('parent_id', $root->id)->orderBy('version', 'desc')->get();
                                $allVersions = \App\Models\Estimate::where('id', $root->id)->orWhere('parent_id', $root->id)->orderBy('version', 'desc')->get();
                            @endphp

                            @foreach($allVersions as $ver)
                                <a href="{{ route('estimates.show', $ver) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $ver->id === $estimate->id ? 'bg-gray-50 font-semibold' : '' }}">
                                    v{{ $ver->version }} - {{ $ver->estimate_date->format('M d') }}
                                    @if($ver->is_current_version) <span class="ml-1 text-xs text-green-600 bg-green-50 px-1 rounded">Current</span> @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('estimates.print', $estimate) }}" target="_blank"
                class="rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Print
            </a>
            <a href="{{ route('estimates.pdf', $estimate) }}"
                class="rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-inset ring-indigo-200 hover:bg-indigo-50">
                Download PDF
            </a>
            <a href="{{ $estimate->public_url }}" target="_blank"
                class="rounded-md bg-indigo-50 px-3 py-2 text-center text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100">
                Public View
            </a>
            <!-- Back Link -->
            <a href="{{ route('estimates.index') }}"
                class="rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Back</a>

            <!-- Admin Actions -->
            <form action="{{ route('estimates.send', $estimate) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                    Send to Client
                </button>
            </form>

            <div x-data="{ open: false }" class="relative inline-block text-left">
                <button @click="open = !open" type="button"
                    class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Admin
                    <svg class="-mr-1 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open = false"
                    class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="py-1">
                        <form action="{{ route('estimates.copy', $estimate) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Duplicate Estimate
                            </button>
                        </form>
                        <hr class="my-1">
                        <div class="px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Override Status</div>
                        @foreach(['draft', 'sent', 'accepted', 'declined', 'expired'] as $status)
                            @if($estimate->status !== $status)
                                <form action="{{ route('estimates.mark-as', [$estimate, $status]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 italic">
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

    <!-- Tracking Stats -->
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-gray-900/5">
            <dt class="text-xs font-semibold text-gray-500 uppercase">Proposal Status</dt>
            <dd class="mt-1 text-lg font-bold text-gray-900">{{ ucfirst($estimate->status) }}</dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-gray-900/5">
            <dt class="text-xs font-semibold text-gray-500 uppercase">Email Opened</dt>
            <dd class="mt-1 text-lg font-bold {{ $estimate->email_opened_at ? 'text-green-600' : 'text-gray-400' }}">
                {{ $estimate->email_opened_at ? $estimate->email_opened_at->diffForHumans() : 'Not Yet' }}
            </dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-gray-900/5">
            <dt class="text-xs font-semibold text-gray-500 uppercase">Client Views</dt>
            <dd class="mt-1 text-lg font-bold text-gray-900">
                {{ $estimate->view_count }} times
                @if($estimate->last_viewed_at)
                    <span class="text-xs font-normal text-gray-500">(Last: {{ $estimate->last_viewed_at->diffForHumans() }})</span>
                @endif
            </dd>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm ring-1 ring-gray-900/5">
            <dt class="text-xs font-semibold text-gray-500 uppercase">Expiry Date</dt>
            <dd class="mt-1 text-lg font-bold {{ $estimate->expiry_date && $estimate->expiry_date < now() ? 'text-red-600' : 'text-gray-900' }}">
                {{ $estimate->expiry_date ? $estimate->expiry_date->format('M d, Y') : 'No Date Set' }}
            </dd>
        </div>
    </div>

    <!-- Approval Status Banner -->
    @if($estimate->approval_chain_id)
        <div class="mb-6 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl px-4 py-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900">Approval Status</h3>
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                    @if($estimate->approval_status === 'approved') bg-green-50 text-green-700 ring-green-600/20
                    @elseif($estimate->approval_status === 'rejected') bg-red-50 text-red-700 ring-red-600/10
                    @elseif($estimate->approval_status === 'submitted') bg-yellow-50 text-yellow-700 ring-yellow-700/10
                    @else bg-gray-50 text-gray-600 ring-gray-500/10
                    @endif">
                    {{ ucfirst($estimate->approval_status) }}
                </span>
            </div>

            @if($estimate->approvalChain)
                <div class="text-sm text-gray-600 mb-4">
                    <strong>Workflow:</strong> {{ $estimate->approvalChain->name }}
                </div>

                <!-- Approval Steps Timeline -->
                <div class="space-y-3">
                    @foreach($estimate->approvalChain->steps as $step)
                        @php
                            $stepApproval = $estimate->approvals()->where('user_id', $step->user_id)->first();
                        @endphp
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                @if($stepApproval && $stepApproval->status === 'approved')
                                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                @elseif($stepApproval && $stepApproval->status === 'rejected')
                                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                    </svg>
                                @elseif($stepApproval && $stepApproval->status === 'pending')
                                    <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ ucfirst(str_replace('_', ' ', $step->role)) }}
                                    @if($step->user)
                                        - {{ $step->user->name }}
                                    @endif
                                </p>
                                @if($stepApproval)
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ ucfirst($stepApproval->status) }} 
                                        @if($stepApproval->updated_at)
                                            on {{ $stepApproval->updated_at->format('M d, Y g:i A') }}
                                        @endif
                                    </p>
                                    @if($stepApproval->comments)
                                        <p class="text-xs text-gray-600 mt-1 italic">"{{ $stepApproval->comments }}"</p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="space-y-6">
        <!-- General Info -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl px-4 py-6 sm:p-8">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Client</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $estimate->client->name ?? 'Unknown' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $estimate->estimate_date->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                            @if($estimate->status === 'accepted') bg-green-50 text-green-700 ring-green-600/20
                            @elseif($estimate->status === 'declined') bg-red-50 text-red-700 ring-red-600/10
                            @elseif($estimate->status === 'sent') bg-blue-50 text-blue-700 ring-blue-700/10
                            @else bg-gray-50 text-gray-600 ring-gray-500/10
                            @endif">
                            {{ ucfirst($estimate->status) }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
        
        <!-- Signature Log -->
        @if($estimate->signed_at)
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl px-4 py-6 sm:p-8 mt-6">
                <h2 class="text-base font-semibold leading-7 text-gray-900 mb-4">Signature Details</h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Signed At</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $estimate->signed_at->format('M d, Y h:i:s A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">IP Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $estimate->signer_ip ?? 'Unknown' }}</dd>
                    </div>
                     <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Location</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $estimate->signer_location ?? 'Unavailable' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Device Info</dt>
                        <dd class="mt-1 text-xs text-gray-500 break-all">{{ $estimate->signer_agent ?? 'Unknown' }}</dd>
                    </div>
                     <div class="sm:col-span-2 mt-4">
                        <dt class="text-xs font-medium text-gray-500 uppercase mb-2">Signature</dt>
                        <dd class="p-4 border border-gray-200 rounded-lg bg-gray-50 inline-block">
                            <img src="{{ $estimate->signature }}" alt="Client Signature" class="max-h-24">
                        </dd>
                    </div>
                </dl>
            </div>
        @endif

        

        
        <!-- Access History -->
        @if($estimate->analytics()->whereIn('action', ['view', 'download'])->exists())
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden mt-6">
                <div class="px-4 py-6 sm:px-6">
                    <h3 class="text-base font-semibold leading-7 text-gray-900">Access History</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Recent views and downloads by the client.</p>
                </div>
                <div class="border-t border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($estimate->analytics()->whereIn('action', ['view', 'download'])->latest()->take(10)->get() as $access)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($access->action === 'download')
                                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">Download</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">View</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $access->created_at->format('M d, Y h:i A') }}
                                        <div class="text-xs text-gray-500">{{ $access->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if(!empty($access->location_json) && is_array($access->location_json))
                                            {{ $access->location_json['city'] ?? '' }}, {{ $access->location_json['country'] ?? '' }}
                                        @else
                                            <span class="text-gray-400">Unknown</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="font-medium text-gray-900">{{ $access->device }}</div>
                                        <div class="text-xs">
                                            {{ $access->browser }} on {{ $access->platform }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                        {{ $access->ip_address }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Items -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
            <div class="px-4 py-6 sm:p-8">
                <h2 class="text-base font-semibold leading-7 text-gray-900 mb-6">
                    @if($estimate->type === 'room_based')
                        Rooms & Items
                    @else
                        Items
                    @endif
                </h2>

                @if($estimate->type === 'room_based')
                    @foreach($estimate->sections as $section)
                        <div class="mb-6 border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $section->name }}</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Price</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Qty</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Tax</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($section->items as $item)
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ $item->name }}</td>
                                            <td class="px-3 py-2 text-sm text-right text-gray-500">
                                                {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="px-3 py-2 text-sm text-right text-gray-500">{{ $item->quantity }}
                                                {{ $item->unit_type }}</td>
                                            <td class="px-3 py-2 text-sm text-right text-gray-500">
                                                {{ $item->tax_1 + $item->tax_2 }}%</td>
                                            <td class="px-3 py-2 text-sm text-right font-medium text-gray-900">
                                                {{ number_format($item->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @else
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Item</th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($estimate->items as $item)
                                <tr>
                                    <td class="px-3 py-4 text-sm">
                                        <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                        @if($item->description)
                                            <div class="text-gray-500 text-xs mt-0.5">{{ $item->description }}</div>
                                        @endif
                                        <div class="text-gray-500 text-xs mt-1">
                                            {{ $item->quantity }} {{ $item->unit_type }} × 
                                            @if($item->is_complimentary)
                                                <span class="line-through text-gray-400">{{ $estimate->currency }} {{ number_format($item->original_price, 2) }}</span>
                                                <span class="ml-1.5 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                    Complimentary
                                                </span>
                                            @else
                                                {{ $estimate->currency }} {{ number_format($item->unit_price, 2) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right text-sm font-medium text-gray-900">
                                        @if($item->is_complimentary)
                                            <span class="text-green-600">{{ $estimate->currency }} 0.00</span>
                                        @else
                                            {{ $estimate->currency }} {{ number_format($item->total, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- Totals -->
                <div class="mt-6 border-t border-gray-200 pt-6">
                    <dl class="space-y-3 text-sm max-w-md ml-auto">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Subtotal</dt>
                            <dd class="font-medium text-gray-900">{{ $estimate->currency }}
                                {{ number_format($estimate->subtotal, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Total Tax</dt>
                            <dd class="font-medium text-gray-900">{{ $estimate->currency }}
                                {{ number_format($estimate->total_tax, 2) }}</dd>
                        </div>
                            @if($estimate->discount_total > 0)
                                <div class="flex justify-between py-2 text-sm">
                                    <span class="text-gray-600">Discount ({{ ucfirst($estimate->discount_type) }})</span>
                                    <span class="text-red-600">-{{ $estimate->currency }} {{ number_format($estimate->discount_total, 2) }}</span>
                                </div>
                            @endif

                            @if($estimate->coupon_discount > 0)
                                <div class="flex justify-between py-2 text-sm">
                                    <span class="text-gray-600">
                                        Coupon Discount
                                        @if($estimate->couponCode)
                                            <code class="ml-1 rounded bg-green-50 px-1.5 py-0.5 text-xs font-mono text-green-700">{{ $estimate->couponCode->code }}</code>
                                        @endif
                                    </span>
                                    <span class="text-green-600">-{{ $estimate->currency }} {{ number_format($estimate->coupon_discount, 2) }}</span>
                                </div>
                            @endif
                        <div class="flex justify-between border-t border-gray-200 pt-3">
                            <dt class="text-base font-semibold text-gray-900">Grand Total</dt>
                            <dd class="text-base font-semibold text-gray-900">{{ $estimate->currency }}
                                {{ number_format($estimate->grand_total, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($estimate->client_note || $estimate->admin_note || $estimate->terms)
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl px-4 py-6 sm:p-8">
                <h2 class="text-base font-semibold leading-7 text-gray-900 mb-4">Notes & Terms</h2>
                @if($estimate->client_note)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Client Note</h3>
                        <p class="text-sm text-gray-600">{{ $estimate->client_note }}</p>
                    </div>
                @endif
                @if($estimate->terms)
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Terms & Conditions</h3>
                        <p class="text-sm text-gray-600">{{ $estimate->terms }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <script>
        function duplicateItem(itemId) {
            if (!confirm('Duplicate this item?')) return;
            
            fetch(`/estimate-items/${itemId}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error duplicating item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error duplicating item');
            });
        }
    </script>
</x-app-layout>