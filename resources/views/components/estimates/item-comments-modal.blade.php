@props(['estimate'])

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
            this.$nextTick(() => { 
                const el = document.getElementById('item-comments-body'); 
                if (el) el.scrollTop = el.scrollHeight; 
            });
        });
    },
    async submit() {
        if (!this.newComment.trim()) return;
        this.isSubmitting = true;
        
        const commentText = this.newComment;
        const tempId = 'temp-' + Date.now();
        
        // Optimistic Update
        this.comments.push({
            id: tempId,
            comment: commentText,
            type: 'internal',
            created_at: new Date().toISOString(),
            formatted_date: 'Just now',
            user: { name: 'You' },
            status: 'pending'
        });
        
        this.newComment = '';
        this.$nextTick(() => { 
            const el = document.getElementById('item-comments-body'); 
            if (el) el.scrollTop = el.scrollHeight; 
        });

        try {
            const response = await fetch(`{{ route('comments.store', $estimate) }}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify({ 
                    commentable_type: 'App\\Models\\EstimateItem', 
                    commentable_id: this.itemId, 
                    comment: commentText 
                })
            });
            
            const data = await response.json();
            if (data.success) {
                // Optionally replace temp comment with real one, 
                // but window.location.reload() or just leaving it is fine for non-Livewire.
                // For better UX we don't reload if we want it truly 'instant'.
                // window.location.reload(); 
            } else {
                throw new Error('Failed');
            }
        } catch (e) {
            console.error(e);
            alert('Failed to post comment');
            this.comments = this.comments.filter(c => c.id !== tempId);
            this.newComment = commentText;
        } finally {
            this.isSubmitting = false;
        }
    },
    async toggleStatus(commentId, currentStatus) {
        const newStatus = currentStatus === 'pending' ? 'clarified' : 'pending';
        // Optimistic update
        const comment = this.comments.find(c => c.id === commentId);
        if (comment) comment.status = newStatus;
        
        try {
            await fetch(`/comments/${commentId}/status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ status: newStatus })
            });
        } catch (e) { 
            console.error(e);
            if (comment) comment.status = currentStatus; // Rollback
        }
    }
}" x-show="isOpen" @keydown.escape.window="isOpen = false" class="relative z-50" aria-labelledby="modal-title"
    role="dialog" aria-modal="true" style="display: none;">

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
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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

@push('scripts')
    <script>
        if (!window.openItemComments) {
            window.openItemComments = function (id, name, comments) {
                window.dispatchEvent(new CustomEvent('open-item-comments', { detail: { id, name, comments } }));
            };
        }
    </script>
@endpush