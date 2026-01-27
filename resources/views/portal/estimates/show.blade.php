<x-portal-layout>
    <div class="sm:mx-auto sm:w-full sm:max-w-[50rem]" x-data="{
        showCommentModal: false,
        showDeclineModal: false,
        showAcceptModal: false,
        signaturePad: null,

        init() {
            this.$watch('showAcceptModal', value => {
                if (value) {
                    this.$nextTick(() => {
                        var canvas = document.getElementById('signature-pad');
                        if (canvas && typeof SignaturePad !== 'undefined') {
                            this.signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
                            window.addEventListener('resize', () => this.resizeCanvas(canvas));
                            this.resizeCanvas(canvas);
                        }
                    });
                }
            });
        },

        resizeCanvas(canvas) {
             if (!this.signaturePad) return;
             var ratio =  Math.max(window.devicePixelRatio || 1, 1);
             canvas.width = canvas.offsetWidth * ratio;
             canvas.height = canvas.offsetHeight * ratio;
             canvas.getContext('2d').scale(ratio, ratio);
             this.signaturePad.clear();
        },

        openCommentModal() {
            this.showCommentModal = true;
            this.$nextTick(() => {
                const container = this.$refs.commentsContainer;
                if (container) container.scrollTop = container.scrollHeight;
            });
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

        @if($errors->any())
            <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
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


        <div class="w-full bg-slate-50 relative">
            @if(isset($htmlContent) && $htmlContent)
                <iframe id="portalPreviewIframe" class="w-full h-[800px] border-0"></iframe>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const iframe = document.getElementById('portalPreviewIframe');
                        if (iframe) {
                            iframe.srcdoc = {!! json_encode($htmlContent) !!};
                            // Auto-resize
                            iframe.onload = function () {
                                iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
                            };
                        }
                    });
                </script>
            @else
                <div class="p-12 text-center text-slate-500">
                    <p>Preview not available.</p>
                </div>
            @endif
        </div>

        <!-- Actions Footer -->
        @if(!in_array($estimate->status, ['accepted', 'declined']))
            <div
                class="bg-slate-50 px-4 py-4 sm:px-6 flex flex-col sm:flex-row justify-end gap-3 border-t border-slate-200">

                <!-- Download PDF -->
                <a href="{{ URL::signedRoute('portal.download', $estimate) }}"
                    class="w-full justify-center inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto">
                    <svg class="h-4 w-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download PDF
                </a>

                <!-- Add Review / Questions -->
                <button type="button" @click="openCommentModal()"
                    class="w-full justify-center inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto">
                    <svg class="h-4 w-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    Add Review / Question
                </button>

                <!-- Trigger Decline -->
                <button type="button" @click="showDeclineModal = true"
                    class="w-full justify-center inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-red-50 sm:w-auto">
                    Decline
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
                                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">
                                                Decline Estimate</h3>
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
                                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">
                                                Accept Estimate</h3>
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
                <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
                @if(session('success') && \Illuminate\Support\Str::contains(session('success'), 'accepted'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var duration = 3 * 1000;
                            var animationEnd = Date.now() + duration;
                            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

                            function randomInRange(min, max) {
                                return Math.random() * (max - min) + min;
                            }

                            var interval = setInterval(function () {
                                var timeLeft = animationEnd - Date.now();

                                if (timeLeft <= 0) {
                                    return clearInterval(interval);
                                }

                                var particleCount = 50 * (timeLeft / duration);
                                // since particles fall down, start a bit higher than random
                                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
                            }, 250);
                        });
                    </script>
                @endif

            </div>
        @endif


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
                                    Comments on Estimate #{{ $estimate->estimate_number }}
                                </h3>
                                <button @click="showCommentModal = false" class="text-slate-400 hover:text-slate-500">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Existing Comments -->
                            <div x-ref="commentsContainer" class="max-h-64 overflow-y-auto mb-4 space-y-3">
                                @if($estimate->comments->where('type', 'client')->isEmpty())
                                    <div class="text-center py-4 text-slate-500">No comments yet. Be the first to comment!
                                    </div>
                                @else
                                    @foreach($estimate->comments->where('type', 'client') as $comment)
                                        <div class="bg-slate-50 rounded-lg p-3">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <p class="text-sm font-semibold text-slate-900">
                                                        {{ $comment->client_name ?: ($comment->user ? $comment->user->name : 'Anonymous') }}
                                                    </p>
                                                    <p class="text-sm text-slate-700 mt-1">{{ $comment->comment }}</p>
                                                    <p class="text-xs text-slate-500 mt-1">
                                                        {{ $comment->created_at->format('M j, Y g:i A') }}
                                                    </p>
                                                </div>
                                                @if($comment->type === 'internal')
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                        Team Reply
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Replies would go here if implemented in view -->
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- New Comment Form -->
                            <div class="border-t border-slate-200 pt-4">
                                <form action="{{ URL::signedRoute('portal.comment', $estimate) }}" method="POST">
                                    @csrf
                                    <label class="block text-sm font-medium text-slate-900 mb-2">Add Your
                                        Comment</label>
                                    <textarea name="comment" rows="3" placeholder="Share your thoughts or questions..."
                                        required
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">{{ old('comment') }}</textarea>

                                    <div class="grid grid-cols-2 gap-3 mt-3">
                                        <input name="client_name" type="text" placeholder="Your name (Optional)"
                                            value="{{ old('client_name') }}"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                        <input name="client_email" type="email" placeholder="Your email (Optional)"
                                            value="{{ old('client_email') }}"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                    </div>

                                    <div class="mt-4 flex justify-end gap-3">
                                        <button @click="showCommentModal = false" type="button"
                                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Post Comment
                                        </button>
                                    </div>
                                </form>
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
    <!-- Request Call FAB -->
    @if(!in_array($estimate->status, ['accepted', 'declined']))
        <div class="fixed bottom-6 right-6 z-40 animate-bounce-in">
            <form action="{{ URL::signedRoute('portal.request-call', $estimate) }}" method="POST"
                onsubmit="return confirm('Would you like us to call you about this estimate?');">
                @csrf
                <button type="submit"
                    class="flex items-center gap-2 bg-indigo-600 text-white rounded-full px-5 py-3 shadow-lg hover:bg-indigo-700 transition-all transform hover:scale-105 font-medium ring-4 ring-white">
                    <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                        </path>
                    </svg>
                    <span>Request a Call</span>
                </button>
            </form>
        </div>
    @endif
</x-portal-layout>