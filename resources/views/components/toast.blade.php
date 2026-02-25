<div x-data="{ 
        messages: [],
        audio: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'),
        remove(id) {
            this.messages = this.messages.filter(m => m.id !== id)
        }
    }" @notify.window="
        let id = Date.now();
        messages.push({
            id: id,
            type: $event.detail.type || 'success',
            content: $event.detail.message || $event.detail[0].message || 'New notification',
            paused: false,
            timer: null,
            progress: 100
        });
        
        if ($event.detail.sound !== false) {
            this.audio.play().catch(e => console.log('Sound playback failed:', e));
        }

        let msg = messages.find(m => m.id === id);
        let duration = 5000;
        let start = Date.now();
        
        msg.timer = setInterval(() => {
            if (!msg.paused) {
                let elapsed = Date.now() - start;
                msg.progress = Math.max(0, 100 - (elapsed / duration * 100));
                if (msg.progress <= 0) {
                    clearInterval(msg.timer);
                    remove(id);
                }
            } else {
                start += 10; 
            }
        }, 10);
    " class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 w-full max-w-sm pointer-events-none">
    <template x-for="message in messages" :key="message.id">
        <div x-transition:enter="transition ease-out duration-500" x-transition:enter-start="translate-x-full opacity-0 scale-95"
            x-transition:enter-end="translate-x-0 opacity-100 scale-100" x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            @mouseenter="message.paused = true" @mouseleave="message.paused = false"
            class="relative overflow-hidden flex items-center p-4 rounded-2xl border border-slate-200 pointer-events-auto backdrop-blur-xl bg-white/95 shadow-2xl shadow-slate-200/50 group">
            
            <!-- Progress Bar -->
            <div class="absolute bottom-0 left-0 h-1 transition-all duration-100 ease-linear"
                :style="`width: ${message.progress}%`"
                :class="{
                    'bg-emerald-500': message.type === 'success',
                    'bg-rose-500': message.type === 'error',
                    'bg-amber-500': message.type === 'warning',
                    'bg-indigo-500': message.type === 'info'
                }"></div>

            <div class="flex-shrink-0 mr-4">
                <template x-if="message.type === 'success'">
                    <div class="bg-emerald-100 rounded-xl p-2.5">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </template>
                <template x-if="message.type === 'error'">
                    <div class="bg-rose-100 rounded-xl p-2.5">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </template>
                <template x-if="message.type === 'warning'">
                    <div class="bg-amber-100 rounded-xl p-2.5">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </template>
            </div>
            
            <div class="flex-1 pr-4">
                <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5" x-text="message.type"></h4>
                <p class="text-sm font-bold text-slate-800 leading-tight" x-text="message.content"></p>
            </div>

            <button @click="clearInterval(message.timer); remove(message.id)" class="p-1 text-slate-300 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>