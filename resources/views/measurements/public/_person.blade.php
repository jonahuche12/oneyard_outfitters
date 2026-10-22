@php
    $base = "items[{$itemId}][people][{$index}]";
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500';
@endphp

<div class="mt-3 rounded-xl border border-slate-200 bg-white p-4"
     data-person data-saved="{{ !empty($saved) ? '1' : '0' }}" data-position="{{ !empty($saved) ? ($values['position'] ?? ($index + 1)) : '' }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0 flex-1">
            <strong class="text-sm font-semibold text-slate-950"
                    data-person-title>Person</strong>
            <p class="mt-1 text-xs text-slate-500"
               data-person-summary hidden></p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                    data-edit-person
                    class="text-sm font-semibold text-amber-700 hover:underline"
                    hidden>
                Edit
            </button>

            @unless($strict)
                <button type="button"
                        data-remove-person
                        class="text-sm font-semibold text-red-700 hover:underline">
                    Remove
                </button>
            @endunless
        </div>
    </div>

    <p data-person-error hidden role="alert"
       class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"></p>

    <div class="mt-4" data-person-fields>
        <input type="text"
               name="{{ $base }}[label]"
               value="{{ $values['label'] ?? '' }}"
               maxlength="255"
               placeholder="Name or label (optional)"
               aria-label="Person name or label"
               class="{{ $input }} mb-3"
               data-person-label>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach($profile as $key => $rule)
                <label class="block text-xs font-medium text-slate-600">
                    {{ $rule['label'] }} (in)
                    <input type="text"
                           inputmode="decimal"
                           name="{{ $base }}[measurements][{{ $key }}]"
                           value="{{ $values['measurements'][$key] ?? '' }}"
                           class="{{ $input }} mt-1"
                           data-measurement>
                </label>
            @endforeach
        </div>

        <input type="text"
               name="{{ $base }}[notes]"
               value="{{ $values['notes'] ?? '' }}"
               maxlength="1000"
               placeholder="Notes (optional)"
               class="{{ $input }} mt-3"
               data-person-notes>

        <div class="mt-4 flex justify-end">
            <button type="button"
                    data-save-person
                    class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Save measurements
            </button>
        </div>
    </div>
</div>
