@props(['disabled' => false, 'error' => false])

<input @disabled($disabled) {{ $attributes->merge([
    'class' => ($error ? 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500') . ' block w-full rounded-lg shadow-sm sm:text-sm'
]) }} @if($error) aria-invalid="true" @endif>