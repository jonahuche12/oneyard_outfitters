{{-- Direct assignment cards for the Store Funds workspace. --}}
@php
    $inputClass = 'block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:min-h-12 sm:py-3 sm:text-base';
    $labelClass = 'mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm';
    $tileLabelClass = 'block text-[10px] font-semibold uppercase tracking-wide sm:text-xs';
    $btnPrimary = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3';
@endphp

<section class="space-y-3 sm:space-y-4">
    <div class="flex items-center gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 sm:h-10 sm:w-10">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M21 3 10 14M21 3l-7 18-4-8-8-4 19-6Z"/>
            </svg>
        </span>
        <div class="min-w-0">
            <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">
                Direct assignments
            </h2>
            <p class="mt-0.5 text-xs leading-5 text-slate-500 sm:mt-1 sm:text-sm">
                Enter cash and bank amounts separately. At least one amount must be greater than zero.
                Direct assignments are approved when created but still require dispatch.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:gap-4 xl:grid-cols-2">
        @forelse ($stores as $directStore)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5 sm:py-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold text-slate-950">
                            {{ $directStore->name }}
                        </h3>
                        <p class="text-[11px] text-slate-500 sm:text-xs">Direct store assignment</p>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('stores.fund-assignments.direct', $directStore) }}"
                      class="space-y-3 p-4 sm:space-y-4 sm:p-5">
                    @csrf

                    <div>
                        <label for="direct-purpose-{{ $directStore->id }}" class="{{ $labelClass }}">Purpose</label>
                        <select id="direct-purpose-{{ $directStore->id }}" name="purpose" required class="{{ $inputClass }}">
                            <option value="daily_transactions">Daily transactions</option>
                            <option value="physical_purchases">Physical purchases</option>
                            <option value="utilities">Utilities</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3">
                            <label for="direct-cash-{{ $directStore->id }}" class="{{ $tileLabelClass }} text-emerald-800">Cash (₦)</label>
                            <input id="direct-cash-{{ $directStore->id }}" name="requested_cash" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="{{ $inputClass }} mt-2">
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-3">
                            <label for="direct-bank-{{ $directStore->id }}" class="{{ $tileLabelClass }} text-blue-800">Bank (₦)</label>
                            <input id="direct-bank-{{ $directStore->id }}" name="requested_bank" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="{{ $inputClass }} mt-2">
                        </div>
                    </div>

                    <div>
                        <label for="direct-reference-{{ $directStore->id }}" class="{{ $labelClass }}">
                            Reference <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <input id="direct-reference-{{ $directStore->id }}" name="reference" maxlength="255" class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label for="direct-note-{{ $directStore->id }}" class="{{ $labelClass }}">
                            Note <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <textarea id="direct-note-{{ $directStore->id }}" name="note" rows="2" class="{{ $inputClass }}"></textarea>
                    </div>

                    <div class="border-t border-slate-100 pt-3 sm:pt-4">
                        <button type="submit" class="{{ $btnPrimary }} w-full sm:w-auto">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Create direct assignment
                        </button>
                    </div>
                </form>
            </article>
        @empty
            <div class="flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center xl:col-span-2">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                    </svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-800">No eligible stores</h3>
                <p class="mt-1 text-xs text-slate-500">
                    There are no active stores available for direct assignment.
                </p>
            </div>
        @endforelse
    </div>
</section>