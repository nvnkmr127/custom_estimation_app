<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Approval Inbox</h1>
            <p class="mt-2 text-sm text-slate-500">Estimates waiting for your review.</p>
        </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl overflow-hidden">
        <ul role="list" class="divide-y divide-slate-100">
            @forelse($pendingEstimates as $estimate)
                <li class="relative flex justify-between gap-x-6 px-4 py-5 hover:bg-slate-50 sm:px-6">
                    <div class="flex min-w-0 gap-x-4">
                        <div class="min-w-0 flex-auto">
                            <p class="text-sm font-semibold leading-6 text-slate-900">
                                <a href="{{ route('estimates.show', $estimate) }}">
                                    <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                    {{ $estimate->title }}
                                </a>
                            </p>
                            <p class="mt-1 flex text-xs leading-5 text-slate-500">
                                <span class="truncate">Estimate #{{ $estimate->estimate_number }}</span>
                                <span class="mx-1">&middot;</span>
                                <span class="truncate">Client: {{ $estimate->client->name ?? 'Unknown' }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-4">
                        <div class="hidden sm:flex sm:flex-col sm:items-end">
                            <p class="text-sm leading-6 text-slate-900">{{ number_format($estimate->grand_total, 2) }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Created
                                {{ $estimate->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <svg class="h-5 w-5 flex-none text-slate-400" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-slate-500">
                    No estimates pending approval.
                </li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">
        {{ $pendingEstimates->links() }}
    </div>
</x-app-layout>