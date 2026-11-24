{{-- Direct assignment cards for the Store Funds workspace. --}}
<section class="space-y-4">
    <div>
        <h2 class="text-base font-bold tracking-tight text-slate-950">
            Direct assignments
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Enter cash and bank amounts separately. At least one amount must be greater than zero.
            Direct assignments are approved when created but still require dispatch.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        @forelse ($stores as $directStore)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-bold text-slate-950">
                        {{ $directStore->name }}
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Direct store assignment</p>
                </div>

                <form method="POST"
                      action="{{ route('stores.fund-assignments.direct', $directStore) }}"
                      class="space-y-4 p-4 sm:p-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Purpose</label>
                        <select name="purpose" required class="oy-input mt-1 w-full">
                            <option value="daily_transactions">Daily transactions</option>
                            <option value="physical_purchases">Physical purchases</option>
                            <option value="utilities">Utilities</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Cash (₦)</label>
                            <input name="requested_cash" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="oy-input mt-1 w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Bank (₦)</label>
                            <input name="requested_bank" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="oy-input mt-1 w-full">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Reference <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <input name="reference" maxlength="255" class="oy-input mt-1 w-full">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Note <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <textarea name="note" rows="2" class="oy-input mt-1 w-full"></textarea>
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                        <button type="submit"
                                class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">
                            Create direct assignment
                        </button>
                    </div>
                </form>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center xl:col-span-2">
                <h3 class="text-sm font-semibold text-slate-900">No eligible stores</h3>
                <p class="mt-1 text-sm text-slate-500">
                    There are no active stores available for direct assignment.
                </p>
            </div>
        @endforelse
    </div>
</section>
