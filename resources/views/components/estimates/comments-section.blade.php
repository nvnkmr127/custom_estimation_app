@props(['estimate'])

<div x-data="{ showCommentsModal: {{ $estimate->comments->where('is_read', false)->isNotEmpty() ? 'true' : 'false' }} }"
    class="bg-white shadow-lg ring-1 ring-slate-200 sm:rounded-2xl px-4 py-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-slate-900">General Discussion</h3>
        @if($estimate->comments->isNotEmpty())
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                {{ $estimate->comments->count() }}
            </span>
        @endif
    </div>

    @if($estimate->comments->isEmpty())
        <div class="flex flex-col items-center justify-center py-6 text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mb-4">
            <svg class="h-8 w-8 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-[10px] uppercase font-bold tracking-widest">No messages yet</p>
        </div>
    @else
        <div class="mb-4">
            <div class="flex -space-x-1.5 overflow-hidden">
                @foreach($estimate->comments->unique('user_id')->take(4) as $comment)
                    @if($comment->isClientComment())
                        <div class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-600 shadow-sm"
                            title="{{ $comment->client_name }}">
                            {{ substr($comment->client_name ?: 'C', 0, 1) }}
                        </div>
                    @else
                        @if($comment->user && $comment->user->avatar)
                            <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white shadow-sm" src="{{ $comment->user->avatar }}"
                                alt="{{ $comment->user->name }}">
                        @else
                            <div class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 shadow-sm"
                                title="{{ $comment->user->name ?? 'Staff' }}">
                                {{ substr($comment->user->name ?? 'S', 0, 1) }}
                            </div>
                        @endif
                    @endif
                @endforeach
                @if($estimate->comments->unique('user_id')->count() > 4)
                    <div
                        class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-slate-50 flex items-center justify-center text-[10px] font-bold text-slate-500 shadow-sm">
                        +{{ $estimate->comments->unique('user_id')->count() - 4 }}
                    </div>
                @endif
            </div>
            <div class="mt-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                    <span class="font-bold text-slate-900">{{ $estimate->comments->last()->isClientComment() ? ($estimate->comments->last()->client_name ?: 'Client') : ($estimate->comments->last()->user->name ?? 'Staff') }}:</span>
                    {{ $estimate->comments->last()->comment }}
                </p>
            </div>
        </div>
    @endif

    <button @click="showCommentsModal = true" type="button"
        class="w-full flex items-center justify-center gap-2 rounded-xl bg-white px-3 py-2.5 text-xs font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition-all active:scale-[0.98]">
        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
        Open Discussion
    </button>

    <!-- Thread Modal -->
    <template x-teleport="body">
        <div x-show="showCommentsModal" class="relative z-50" aria-labelledby="modal-title" role="dialog"
            aria-modal="true" style="display: none;">
            
            <!-- Backdrop -->
            <div x-show="showCommentsModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity">
            </div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="showCommentsModal" @click.outside="showCommentsModal = false"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-xl flex flex-col max-h-[90vh]">

                        <!-- Header -->
                        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 sticky top-0 z-10">
                            <h3 class="text-lg font-bold text-slate-900" id="modal-title">Discussion Thread</h3>
                            <button type="button" @click="showCommentsModal = false"
                                class="text-slate-400 hover:text-slate-600 transition-colors">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body (Scrollable) -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50/50 min-h-[40vh] scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100"
                            id="comments-thread-body" x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                            @if($estimate->comments->isEmpty())
                                <div class="flex flex-col items-center justify-center h-full text-slate-400 py-10">
                                    <svg class="w-12 h-12 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-semibold text-slate-900">No comments yet</h3>
                                    <p class="mt-1 text-sm text-slate-500">Start the conversation with the client.</p>
                                </div>
                            @else
                                @foreach($estimate->comments as $comment)
                                    <div class="flex {{ $comment->isClientComment() ? 'justify-start' : 'justify-end' }}">
                                        <div class="max-w-[85%]">
                                            <div class="flex items-end gap-2 mb-1 {{ $comment->isClientComment() ? '' : 'justify-end' }}">
                                                @if($comment->isClientComment())
                                                    <span class="text-xs font-bold text-slate-700">{{ $comment->client_name ?: 'Client' }}</span>
                                                @endif
                                                <span class="text-[10px] text-slate-400 font-medium">{{ $comment->created_at->format('M j, g:i A') }}</span>
                                                @if(!$comment->isClientComment())
                                                    <span class="text-xs font-bold text-slate-700">{{ $comment->user->name ?? 'Staff' }}</span>
                                                @endif
                                            </div>

                                            <div class="p-3 rounded-2xl shadow-sm text-sm leading-relaxed relative group {{ $comment->isClientComment() ? 'bg-white border border-slate-200 text-slate-700 rounded-tl-none' : 'bg-indigo-600 text-white rounded-tr-none' }}">
                                                <div class="whitespace-pre-wrap">
                                                    {{ $comment->comment }}
                                                </div>

                                                <!-- Action bar inside chat bubble -->
                                                <div class="mt-2 pt-2 border-t flex items-center justify-between {{ $comment->isClientComment() ? 'border-slate-100' : 'border-indigo-500/30' }}">
                                                    <div class="flex items-center gap-2">
                                                        @if($comment->status === 'clarified')
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ $comment->isClientComment() ? 'bg-green-100 text-green-800' : 'bg-green-500 text-white' }}">Clarified</span>
                                                        @elseif($comment->status === 'pending' && $comment->parent_id === null)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ $comment->isClientComment() ? 'bg-yellow-100 text-yellow-800' : 'bg-yellow-500 text-white' }}">Pending</span>
                                                        @endif
                                                    </div>

                                                    @if(auth()->check())
                                                        <button @click="toggleCommentStatus({{ $comment->id }}, '{{ $comment->status }}')" type="button"
                                                            class="text-[10px] underline hover:no-underline font-medium transition-colors {{ $comment->isClientComment() ? 'text-slate-400 hover:text-indigo-600' : 'text-indigo-200 hover:text-white' }}">
                                                            {{ $comment->status === 'pending' ? 'Mark Resolved' : 'Reopen' }}
                                                        </button>
                                                    @endif
                                                </div>

                                                @if($comment->commentable_type === 'App\Models\EstimateItem' && $comment->commentable)
                                                    @php
                                                        $itemComments = $comment->commentable->comments->flatMap(function ($c) {
                                                            return collect([$c])->merge($c->replies);
                                                        })->unique('id')->sortBy('created_at')->values()->map(function ($c) {
                                                            $c->formatted_date = $c->created_at->format('M j, g:i A');
                                                            return $c;
                                                        });
                                                    @endphp
                                                    <button type="button" 
                                                        @click="showCommentsModal = false; openItemComments({{ $comment->commentable->id }}, {{ Js::from($comment->commentable->name) }}, {{ Js::from($itemComments) }})"
                                                        class="mt-1.5 text-[10px] font-bold opacity-60 {{ $comment->isClientComment() ? 'text-indigo-600 hover:text-indigo-800' : 'text-indigo-200 hover:text-white' }} text-left block transition-colors">
                                                        RE: <span class="uppercase tracking-tight underline">{{ $comment->commentable->name }}</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Footer / Reply Form -->
                        <div class="bg-white px-4 py-4 border-t border-slate-100 w-full">
                            <form action="{{ route('estimates.reply', $estimate) }}" method="POST" class="flex gap-2">
                                @csrf
                                <textarea name="comment" rows="1"
                                    class="flex-1 rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 resize-none py-3 text-sm shadow-sm"
                                    placeholder="Write a message..."></textarea>
                                <button type="submit"
                                    class="p-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 shadow-md transition-all active:scale-95 flex items-center justify-center shrink-0">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>