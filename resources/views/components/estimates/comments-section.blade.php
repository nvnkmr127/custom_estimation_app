@props(['estimate'])

<div x-data="{ showCommentsModal: {{ $estimate->comments->where('is_read', false)->isNotEmpty() ? 'true' : 'false' }} }"
    class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
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
                        <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600"
                            title="{{ $comment->client_name }}">
                            {{ substr($comment->client_name ?: 'C', 0, 1) }}
                        </div>
                    @else
                        @if($comment->user && $comment->user->avatar)
                            <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white" src="{{ $comment->user->avatar }}"
                                alt="{{ $comment->user->name }}">
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
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity">
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
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg flex flex-col max-h-[85vh]">

                        <!-- Header -->
                        <div
                            class="bg-white px-4 py-3 border-b border-gray-200 flex justify-between items-center sticky top-0 z-10">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
                                Discussion Thread</h3>
                            <button type="button" @click="showCommentsModal = false"
                                class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body (Scrollable) -->
                        <div class="px-4 py-4 overflow-y-auto flex-1 bg-slate-50 space-y-4 min-h-[40vh]"
                            id="comments-thread-body" x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                            @if($estimate->comments->isEmpty())
                                <div class="text-center py-8">
                                    <div
                                        class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 mb-3">
                                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </div>
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No comments yet</h3>
                                    <p class="mt-1 text-sm text-gray-500">Start the conversation with the
                                        client.</p>
                                </div>
                            @else
                                @foreach($estimate->comments as $comment)
                                    <div class="flex {{ $comment->isClientComment() ? 'justify-start' : 'justify-end' }}">
                                        <div
                                            class="flex max-w-[85%] {{ $comment->isClientComment() ? 'flex-row' : 'flex-row-reverse' }} items-end gap-2">
                                            <!-- Avatar -->
                                            <div class="flex-shrink-0">
                                                @if($comment->isClientComment())
                                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-600 ring-1 ring-white shadow-sm"
                                                        title="{{ $comment->client_name }}">
                                                        {{ substr($comment->client_name ?: 'C', 0, 1) }}
                                                    </div>
                                                @else
                                                    @if($comment->user && $comment->user->avatar)
                                                        <img class="h-8 w-8 rounded-full bg-gray-50 ring-1 ring-white shadow-sm"
                                                            src="{{ $comment->user->avatar }}" alt=""
                                                            title="{{ $comment->user->name }}">
                                                    @else
                                                        <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold text-white ring-1 ring-white shadow-sm"
                                                            title="{{ $comment->user->name ?? 'Staff' }}">
                                                            {{ substr($comment->user->name ?? 'S', 0, 1) }}
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>

                                            <!-- Message Bubble -->
                                            <div
                                                class="{{ $comment->isClientComment() ? 'bg-white rounded-tl-2xl rounded-tr-2xl rounded-br-2xl text-slate-700' : 'bg-indigo-600 rounded-tl-2xl rounded-tr-2xl rounded-bl-2xl text-white' }} p-3 shadow-sm ring-1 ring-black/5 text-sm">
                                                <div
                                                    class="font-semibold text-[11px] mb-1 opacity-90 {{ $comment->isClientComment() ? 'text-slate-500' : 'text-indigo-100' }}">
                                                    {{ $comment->isClientComment() ? ($comment->client_name ?: 'Client') : ($comment->user->name ?? 'Staff') }}
                                                </div>
                                                <div class="whitespace-pre-wrap leading-relaxed">
                                                    {{ $comment->comment }}
                                                </div>
                                                <div
                                                    class="flex items-center justify-between mt-1 pt-2 border-t {{ $comment->isClientComment() ? 'border-slate-100' : 'border-indigo-500/30' }}">
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="text-[10px] opacity-70 {{ $comment->isClientComment() ? 'text-slate-400' : 'text-indigo-200' }}">
                                                            {{ $comment->created_at->format('M j, g:i A') }}
                                                        </div>
                                                        <!-- Status Badge -->
                                                        @if($comment->status === 'clarified')
                                                            <span
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium {{ $comment->isClientComment() ? 'bg-green-100 text-green-800' : 'bg-green-500 text-white' }}">
                                                                Clarified
                                                            </span>
                                                        @elseif($comment->status === 'pending' && $comment->parent_id === null)
                                                            <span
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium {{ $comment->isClientComment() ? 'bg-yellow-100 text-yellow-800' : 'bg-yellow-500 text-white' }}">
                                                                Pending
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <!-- Resolve/Reopen Action -->
                                                    @if(auth()->check())
                                                        <div class="flex items-center">
                                                            <button
                                                                @click="toggleCommentStatus({{ $comment->id }}, '{{ $comment->status }}')"
                                                                type="button"
                                                                class="text-[10px] underline hover:no-underline {{ $comment->isClientComment() ? 'text-slate-500 hover:text-indigo-600' : 'text-indigo-200 hover:text-white' }}">
                                                                {{ $comment->status === 'pending' ? 'Mark Clarified' : 'Reopen' }}
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Context Info (Item Name) -->
                                                @if($comment->commentable_type === 'App\Models\EstimateItem' && $comment->commentable)
                                                    <div
                                                        class="mt-1 text-[10px] italic opacity-60 {{ $comment->isClientComment() ? 'text-slate-500' : 'text-indigo-200' }}">
                                                        Re: {{ $comment->commentable->name }}
                                                    </div>
                                                @endif
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
                                    <textarea id="markdown-comment" name="comment" rows="3"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="Write a reply..."></textarea>
                                    <div class="mt-2 flex justify-end">
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
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