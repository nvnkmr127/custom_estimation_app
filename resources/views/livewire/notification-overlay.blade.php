<div
    x-data="{ 
        notifications: [],
        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id)
        }
    }"
    @notify.window="
        const id = Date.now()
        notifications.push({ id, message: $event.detail.message, type: $event.detail.type || 'info' })
        setTimeout(() => remove(id), 5000)
    "
    class="fixed bottom-0 right-0 p-6 space-y-4 z-50 pointer-events-none"
>
    <template x-for="n in notifications" :key="n.id">
        <div 
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
            :class="{
                'bg-blue-50 ring-blue-500/20': n.type === 'info',
                'bg-green-50 ring-green-500/20': n.type === 'success',
                'bg-red-50 ring-red-500/20': n.type === 'error',
                'bg-amber-50 ring-amber-500/20': n.type === 'warning'
            }"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <template x-if="n.type === 'success'">
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-400" />
                        </template>
                        <template x-if="n.type === 'error'">
                            <x-heroicon-o-x-circle class="h-6 w-6 text-red-400" />
                        </template>
                        <template x-if="n.type === 'info'">
                            <x-heroicon-o-information-circle class="h-6 w-6 text-blue-400" />
                        </template>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-slate-900" x-text="n.message"></p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button 
                            @click="remove(n.id)"
                            class="rounded-md inline-flex text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <span class="sr-only">Close</span>
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
