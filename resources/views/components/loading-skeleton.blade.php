@props(['type' => 'card', 'count' => 3])

@if($type === 'card')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @for($i = 0; $i < $count; $i++)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden animate-pulse">
                <div class="aspect-[4/3] bg-slate-200"></div>
                <div class="p-5 space-y-3">
                    <div class="h-3 bg-slate-200 rounded w-1/3"></div>
                    <div class="h-5 bg-slate-200 rounded w-3/4"></div>
                    <div class="h-3 bg-slate-200 rounded w-1/2"></div>
                    <div class="flex items-center justify-between pt-3">
                        <div class="h-6 bg-slate-200 rounded w-1/4"></div>
                        <div class="h-4 bg-slate-200 rounded w-1/4"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
@elseif($type === 'table')
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="animate-pulse">
            @for($i = 0; $i < $count; $i++)
                <div class="flex items-center gap-4 px-6 py-4 border-b border-slate-100 last:border-0">
                    <div class="h-12 w-12 bg-slate-200 rounded-xl shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-slate-200 rounded w-1/3"></div>
                        <div class="h-3 bg-slate-200 rounded w-1/4"></div>
                    </div>
                    <div class="h-4 bg-slate-200 rounded w-20"></div>
                    <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
                </div>
            @endfor
        </div>
    </div>
@elseif($type === 'list')
    <div class="space-y-4">
        @for($i = 0; $i < $count; $i++)
            <div class="bg-white rounded-xl border border-slate-200 p-4 animate-pulse">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 bg-slate-200 rounded-full shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-slate-200 rounded w-1/2"></div>
                        <div class="h-3 bg-slate-200 rounded w-1/3"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
@elseif($type === 'text')
    <div class="space-y-3 animate-pulse">
        @for($i = 0; $i < $count; $i++)
            <div class="h-4 bg-slate-200 rounded {{ $i % 3 === 0 ? 'w-full' : ($i % 3 === 1 ? 'w-5/6' : 'w-4/5') }}"></div>
        @endfor
    </div>
@endif