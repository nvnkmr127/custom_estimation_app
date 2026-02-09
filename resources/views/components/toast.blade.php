<div x-data="{ 
        messages: [],
        remove(id) {
            this.messages = this.messages.filter(m => m.id !== id)
        }
    }" @notify.window="
        let id = Date.now();
        messages.push({
            id: id,
            type: $event.detail.type || 'success',
            content: $event.detail.message || $event.detail[0].message
        });
        setTimeout(() => remove(id), 5000)
    " class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 w-full max-w-sm">
    <template x-for="message in messages" :key="message.id">
        <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" :class="{
                'bg-white border-green-100 text-green-800 shadow-green-100/50': message.type === 'success',
                'bg-white border-red-100 text-red-800 shadow-red-100/50': message.type === 'error',
                'bg-white border-amber-100 text-amber-800 shadow-amber-100/50': message.type === 'warning'
            }" class="flex items-center p-4 rounded-2xl border shadow-xl backdrop-blur-sm bg-white/90">
            <div class="flex-shrink-0 mr-3">
                <template x-if="message.type === 'success'">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <template x-if="message.type === 'error'">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>
                <template x-if="message.type === 'warning'">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
            </div>
            <p class="text-sm font-bold flex-1" x-text="message.content"></p>
            <button @click="remove(message.id)" class="ml-4 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>