@props(['estimate'])

<div x-data="itemCommentsModal" x-show="isOpen" @keydown.escape.window="isOpen = false" class="relative z-50"
    aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
    <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="isOpen" @click.outside="isOpen = false" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg flex flex-col max-h-[85vh]">
                <div
                    class="bg-white px-4 py-3 border-b border-gray-200 flex justify-between items-center sticky top-0 z-10">
                    <div>
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Comments for Item</h3>
                        <p class="text-xs text-slate-500" x-text="itemName"></p>
                    </div>
                    <button type="button" @click="isOpen = false" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-4 py-4 overflow-y-auto flex-1 bg-slate-50 space-y-4 min-h-[40vh]"
                    id="item-comments-body">
                    <template x-if="comments.length === 0">
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
                            <p class="mt-1 text-sm text-gray-500">Add a comment to this item.</p>
                        </div>
                    </template>
                    <template x-for="comment in comments" :key="comment.id">
                        <div class="flex" :class="comment.type === 'client' ? 'justify-start' : 'justify-end'">
                            <div class="max-w-[85%]">
                                <div class="p-3 shadow-sm ring-1 ring-black/5 text-sm"
                                    :class="comment.type === 'client' ? 'bg-white rounded-tl-2xl rounded-tr-2xl rounded-br-2xl text-slate-700' : 'bg-indigo-600 rounded-tl-2xl rounded-tr-2xl rounded-bl-2xl text-white'">
                                    <div class="whitespace-pre-wrap leading-relaxed" x-text="comment.comment">
                                    </div>

                                    <div class="flex items-center justify-between mt-1 pt-2 border-t"
                                        :class="comment.type === 'client' ? 'border-slate-100' : 'border-indigo-500/30'">
                                        <div class="flex items-center gap-2">
                                            <div class="text-[10px] opacity-70"
                                                :class="comment.type === 'client' ? 'text-slate-400' : 'text-indigo-200'"
                                                x-text="comment.formatted_date"></div>
                                            <!-- Status Badge -->
                                            <template x-if="comment.status === 'clarified'">
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium"
                                                    :class="comment.type === 'client' ? 'bg-green-100 text-green-800' : 'bg-green-500 text-white'">Clarified</span>
                                            </template>
                                            <template x-if="comment.status === 'pending'">
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium"
                                                    :class="comment.type === 'client' ? 'bg-yellow-100 text-yellow-800' : 'bg-yellow-500 text-white'">Pending</span>
                                            </template>
                                        </div>
                                        @if(auth()->check())
                                            <button @click="toggleStatus(comment.id, comment.status)" type="button"
                                                class="text-[10px] underline hover:no-underline"
                                                :class="comment.type === 'client' ? 'text-slate-500 hover:text-indigo-600' : 'text-indigo-200 hover:text-white'">
                                                <span
                                                    x-text="comment.status === 'pending' ? 'Mark Clarified' : 'Reopen'"></span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="bg-white px-4 py-4 border-t border-gray-200 w-full">
                    <textarea x-model="newComment" rows="2"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="Write a comment..."></textarea>
                    <div class="mt-2 flex justify-end">
                        <button type="button" @click="submit" :disabled="isSubmitting"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('itemCommentsModal', () => ({
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
                    try {
                        const response = await fetch(`{{ route('comments.store', $estimate) }}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ commentable_type: 'App\\Models\\EstimateItem', commentable_id: this.itemId, comment: this.newComment })
                        });
                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Failed to post comment');
                    } finally {
                        this.isSubmitting = false;
                    }
                },
                async toggleStatus(commentId, currentStatus) {
                    const newStatus = currentStatus === 'pending' ? 'clarified' : 'pending';
                    try {
                        const response = await fetch(`/comments/${commentId}/status`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ status: newStatus })
                        });
                        if (response.ok) { window.location.reload(); }
                    } catch (e) { console.error(e); }
                }
            }));
        });

        if (!window.openItemComments) {
            window.openItemComments = function (id, name, comments) {
                window.dispatchEvent(new CustomEvent('open-item-comments', { detail: { id, name, comments } }));
            };
        }
    </script>
@endpush