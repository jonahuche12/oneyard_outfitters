@props(['label'])
<div {{ $attributes->merge(['class' => 'flex flex-col gap-0.5 py-2 sm:flex-row sm:justify-between sm:gap-6']) }}>
    <dt class="text-sm text-slate-500">{{ $label }}</dt>
    <dd class="text-sm font-medium text-slate-900 sm:text-right">{{ $slot }}</dd>
</div>
