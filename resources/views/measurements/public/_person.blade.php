@php
    $base = "items[{$itemId}][people][{$index}]";
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500';
@endphp
<div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4" data-person>
    <div class="mb-3 flex items-center justify-between">
        <strong class="text-sm text-slate-950" data-person-title>Person</strong>
        @if(! $strict)
            <button type="button" data-remove-person class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
        @endif
    </div>
    <input type="text" name="{{ $base }}[label]" value="{{ $values['label'] ?? '' }}" maxlength="255" placeholder="Name or label (optional)" class="{{ $input }} mb-3">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach($profile as $key => $rule)
            <label class="block text-xs font-medium text-slate-600">
                {{ $rule['label'] }} (in)
                <input type="text" inputmode="decimal" name="{{ $base }}[measurements][{{ $key }}]" value="{{ $values['measurements'][$key] ?? '' }}" class="{{ $input }} mt-1">
            </label>
        @endforeach
    </div>
    <input type="text" name="{{ $base }}[notes]" value="{{ $values['notes'] ?? '' }}" maxlength="1000" placeholder="Notes (optional)" class="{{ $input }} mt-3">
</div>
