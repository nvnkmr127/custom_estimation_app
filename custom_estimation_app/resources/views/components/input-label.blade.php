@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-slate-700 mb-1.5']) }}>
    {{ $value ?? $slot }}
    @if($required)
        <span class="text-rose-500">*</span>
    @endif
</label>