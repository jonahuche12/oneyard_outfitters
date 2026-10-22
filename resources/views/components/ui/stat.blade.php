@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'slate'])
@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-600',
        'green' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'red' => 'bg-red-100 text-red-700',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm']) }}>
    @if($icon)
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['slate'] }}">
            <x-ui.icon :name="$icon" class="h-5 w-5" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p class="mt-1 truncate text-lg font-semibold text-slate-900">{{ $value }}</p>
        @if($hint)<p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>@endif
    </div>
</div>
