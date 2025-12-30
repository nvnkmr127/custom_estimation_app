<x-portal-layout>
    <div class="sm:mx-auto sm:w-full sm:max-w-[50rem]" x-data="{
        showCommentModal: false,
        commentableType: '',
        commentableId: null,
        commentableName: '',
        comments: [],
        newComment: '',
        clientName: '',
        clientEmail: '',
        loading: false,
        
        openCommentModal(type, id, name) {
            this.commentableType = type;
            this.commentableId = id;
            this.commentableName = name;
            this.showCommentModal = true;
            this.loadComments();
        },
        
        async loadComments() {
            this.loading = true;
            try {
                const response = await fetch('/estimates/{{ $estimate->id }}/comments');
                const allComments = await response.json();
                this.comments = allComments.filter(c => 
                    c.commentable_type === this.commentableType && 
                    c.commentable_id == this.commentableId
                );
            } catch (error) {
                console.error('Error loading comments:', error);
            }
            this.loading = false;
        },
        
        async submitComment() {
            if (!this.newComment.trim()) return;
            
            this.loading = true;
            try {
                const response = await fetch('/estimates/{{ $estimate->id }}/comments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        commentable_type: this.commentableType,
                        commentable_id: this.commentableId,
                        comment: this.newComment,
                        client_name: this.clientName,
                        client_email: this.clientEmail
                    })
                });
                
                if (response.ok) {
                    this.newComment = '';
                    await this.loadComments();
                }
            } catch (error) {
                console.error('Error submitting comment:', error);
            }
            this.loading = false;
        }
    }">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($estimate->status === 'accepted')
            <div class="mb-6 rounded-md bg-emerald-50 p-4 border border-emerald-200 text-center">
                <p class="text-lg font-semibold text-emerald-800">✓ This estimate has been accepted.</p>
            </div>
        @elseif($estimate->status === 'declined')
            <div class="mb-6 rounded-md bg-rose-50 p-4 border border-rose-200 text-center">
                <p class="text-lg font-semibold text-rose-800">✗ This estimate has been declined.</p>
            </div>
        @endif


        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="px-4 py-5 sm:px-6 flex justify-between items-start border-b border-slate-200 bg-slate-50/50">
                <div>
                    <!-- Dynamic Logo -->
                    @if(isset($app_settings['app_logo']) && $app_settings['app_logo'])
                        <img src="{{ asset($app_settings['app_logo']) }}" alt="{{ config('app.name') }}"
                            class="h-10 w-auto mb-4">
                    @else
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">{{ config('app.name') }}</h2>
                    @endif

                    <h3 class="text-lg leading-6 font-medium text-slate-900">Estimate #{{ $estimate->estimate_number }}
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $estimate->title }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-slate-500">Date: <span
                            class="font-semibold text-slate-900">{{ $estimate->estimate_date->format('M d, Y') }}</span>
                    </p>
                    @if($estimate->expiry_date)
                        <p class="text-sm text-slate-500">Expires: <span
                                class="font-semibold text-slate-900">{{ $estimate->expiry_date->format('M d, Y') }}</span>
                        </p>
                    @endif
                    <div class="mt-2">
                        <a href="{{ route('estimates.pdf', $estimate) }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Download PDF &darr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="px-4 py-5 sm:p-6">
                <!-- Line Items (Unified list for now, easy to group later if needed) -->
                <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-slate-200">
                        @foreach($estimate->sections as $section)
                            <!-- Section Header -->
                            <li
                                class="py-5 bg-slate-50/50 -mx-4 px-4 sm:-mx-6 sm:px-6 border-b border-t border-slate-100 mt-5 first:mt-0">
                                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wide">{{ $section->name }}
                                </h4>
                            </li>

                            @foreach($section->items as $item)
                                <li class="py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-slate-900">{{ $item->name }}</p>
                                            <p class="truncate text-sm text-slate-500">
                                                {{ $item->quantity }} {{ $item->unit_type }} x
                                                {{ $estimate->currency }}{{ number_format($item->unit_price, 2) }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ $estimate->currency }}{{ number_format($item->total, 2) }}
                                            </p>
                                            <button
                                                @click="openCommentModal('App\\\\Models\\\\EstimateItem', {{ $item->id }}, '{{ $item->name }}')"
                                                class="text-indigo-600 hover:text-indigo-800 transition-colors">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                            <!-- Section Subtotal -->
                            <li class="py-3 text-right">
                                <span class="text-sm text-slate-500 mr-2">Section Total:</span>
                                <span
                                    class="text-sm font-semibold text-slate-900">{{ $estimate->currency }}{{ number_format($section->items->sum('total'), 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Totals -->
                <div class="mt-8 border-t border-slate-200 pt-8 flex justify-end">
                    <div class="w-full sm:w-1/2 lg:w-1/3">
                        <div class="flex justify-between py-2">
                            <span class="text-sm text-slate-500">Subtotal</span>
                            <span
                                class="text-sm font-medium text-slate-900">{{ $estimate->currency }}{{ number_format($estimate->subtotal, 2) }}</span>
                        </div>
                        @if($estimate->discount_total > 0)
                            <div class="flex justify-between py-2 text-rose-600">
                                <span class="text-sm">Discount</span>
                                <span
                                    class="text-sm font-medium">-{{ $estimate->currency }}{{ number_format($estimate->discount_total, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between py-2">
                            <span class="text-sm text-slate-500">Tax</span>
                            <span
                                class="text-sm font-medium text-slate-900">{{ $estimate->currency }}{{ number_format($estimate->total_tax, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-t border-slate-200 mt-2">
                            <span class="text-base font-bold text-slate-900">Grand Total</span>
                            <span
                                class="text-base font-bold text-indigo-600">{{ $estimate->currency }}{{ number_format($estimate->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Footer -->
            @if(!in_array($estimate->status, ['accepted', 'declined']))
                <div x-data="{ showDeclineModal: false, showAcceptModal: false, signaturePad: null }" x-init="
                                    $watch('showAcceptModal', value => {
                                        if (value) {
                                            $nextTick(() => {
                                                var canvas = document.getElementById('signature-pad');
                                                if (canvas) {
                                                    signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
                                                    window.addEventListener('resize', resizeCanvas);
                                                    resizeCanvas();

                                                    function resizeCanvas() {
                                                        var ratio =  Math.max(window.devicePixelRatio || 1, 1);
                                                        canvas.width = canvas.offsetWidth * ratio;
                                                        canvas.height = canvas.offsetHeight * ratio;
                                                        canvas.getContext('2d').scale(ratio, ratio);
                                                        signaturePad.clear(); // otherwise isEmpty() might return incorrect value
                                                    }
                                                }
                                            });
                                        }
                                    })
                                "
                    class="bg-slate-50 px-4 py-4 sm:px-6 flex flex-col sm:flex-row justify-end gap-3 border-t border-slate-200">

                    <!-- Trigger Decline -->
                    <button type="button" @click="showDeclineModal = true"
                        class="w-full justify-center inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto">
                        Decline Estimate
                    </button>

                    <!-- Trigger Accept -->
                    <button type="button" @click="showAcceptModal = true"
                        class="w-full justify-center inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:w-auto">
                        Accept Estimate
                    </button>

                    <!-- Decline Modal -->
                    <div x-show="showDeclineModal" class="relative z-10" aria-labelledby="modal-title" role="dialog"
                        aria-modal="true" style="display: none;">
                        <div x-show="showDeclineModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>

                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div x-show="showDeclineModal" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                                    <form action="{{ URL::signedRoute('portal.decline', $estimate) }}" method="POST">
                                        @csrf
                                        <div>
                                            <div
                                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                </svg>
                                            </div>
                                            <div class="mt-3 text-center sm:mt-5">
                                                <h3 class="text-base font-semibold leading-6 text-slate-900"
                                                    id="modal-title">Decline Estimate</h3>
                                                <div class="mt-2">
                                                    <p class="text-sm text-slate-500">Please provide a reason for declining
                                                        so we can better serve you.</p>
                                                    <textarea name="client_notes" rows="3"
                                                        class="mt-3 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        placeholder="Reason for declining..." required></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                            <button type="submit"
                                                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 sm:col-start-2">Decline</button>
                                            <button type="button" @click="showDeclineModal = false"
                                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:col-start-1 sm:mt-0">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accept Modal -->
                    <div x-show="showAcceptModal" class="relative z-10" aria-labelledby="modal-title" role="dialog"
                        aria-modal="true" style="display: none;">
                        <div x-show="showAcceptModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>

                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div x-show="showAcceptModal" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                                    <form action="{{ URL::signedRoute('portal.accept', $estimate) }}" method="POST"
                                        @submit.prevent="if(signaturePad.isEmpty()){ alert('Please provide a signature.'); return false; } document.getElementById('signature_input').value = signaturePad.toDataURL(); $el.submit();">
                                        @csrf
                                        <input type="hidden" name="signature" id="signature_input">
                                        <div>
                                            <div
                                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                                                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </div>
                                            <div class="mt-3 text-center sm:mt-5">
                                                <h3 class="text-base font-semibold leading-6 text-slate-900"
                                                    id="modal-title">Accept Estimate</h3>
                                                <div class="mt-2">
                                                    <p class="text-sm text-slate-500 mb-4">Please sign below to accept this
                                                        estimate.</p>
                                                    <div class="border border-slate-300 rounded-md bg-slate-50 touch-none">
                                                        <canvas id="signature-pad"
                                                            class="w-full h-40 rounded-md cursor-crosshair"></canvas>
                                                    </div>
                                                    <button type="button" @click="signaturePad.clear()"
                                                        class="text-xs text-red-600 mt-2 hover:underline">Clear
                                                        Signature</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                            <button type="submit"
                                                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Sign
                                                & Accept</button>
                                            <button type="button" @click="showAcceptModal = false"
                                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:col-start-1 sm:mt-0">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
                </div>
            @endif
        </div>

        <!-- Comment Modal -->
        <div x-show="showCommentModal" class="relative z-50" style="display: none;">
            <div x-show="showCommentModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity">
            </div>

            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="showCommentModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-slate-900">
                                    Comments on <span x-text="commentableName"></span>
                                </h3>
                                <button @click="showCommentModal = false" class="text-slate-400 hover:text-slate-500">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Existing Comments -->
                            <div class="max-h-64 overflow-y-auto mb-4 space-y-3">
                                <template x-if="loading">
                                    <div class="text-center py-4 text-slate-500">Loading...</div>
                                </template>

                                <template x-if="!loading && comments.length === 0">
                                    <div class="text-center py-4 text-slate-500">No comments yet. Be the first to
                                        comment!</div>
                                </template>

                                <template x-for="comment in comments" :key="comment.id">
                                    <div class="bg-slate-50 rounded-lg p-3">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="text-sm font-semibold text-slate-900"
                                                    x-text="comment.client_name || comment.user?.name || 'Anonymous'">
                                                </p>
                                                <p class="text-sm text-slate-700 mt-1" x-text="comment.comment"></p>
                                                <p class="text-xs text-slate-500 mt-1"
                                                    x-text="new Date(comment.created_at).toLocaleString()"></p>
                                            </div>
                                            <span x-show="comment.type === 'internal'"
                                                class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                Team Reply
                                            </span>
                                        </div>

                                        <!-- Replies -->
                                        <template x-if="comment.replies && comment.replies.length > 0">
                                            <div class="ml-4 mt-2 space-y-2">
                                                <template x-for="reply in comment.replies" :key="reply.id">
                                                    <div class="bg-white rounded p-2 border border-slate-200">
                                                        <p class="text-xs font-semibold text-slate-900"
                                                            x-text="reply.user?.name || 'Team'"></p>
                                                        <p class="text-xs text-slate-700 mt-1" x-text="reply.comment">
                                                        </p>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <!-- New Comment Form -->
                            <div class="border-t border-slate-200 pt-4">
                                <label class="block text-sm font-medium text-slate-900 mb-2">Add Your Comment</label>
                                <textarea x-model="newComment" rows="3"
                                    placeholder="Share your thoughts or questions..."
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm"></textarea>

                                <div class="grid grid-cols-2 gap-3 mt-3">
                                    <input x-model="clientName" type="text" placeholder="Your name"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                    <input x-model="clientEmail" type="email" placeholder="Your email"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button @click="showCommentModal = false" type="button"
                                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                                        Cancel
                                    </button>
                                    <button @click="submitComment()" :disabled="loading || !newComment.trim()"
                                        type="button"
                                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-show="!loading">Post Comment</span>
                                        <span x-show="loading">Posting...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</x-portal-layout>