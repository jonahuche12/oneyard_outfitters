@php
    $linkUser = auth()->user();
    $canManageLinks = $linkUser && $linkUser->is_active && $linkUser->hasPermission('measurements.links.manage')
        && ($linkUser->roles()->whereIn('slug', ['admin', 'super-admin'])->exists()
            || (int) optional($order->currentAssignment)->user_id === (int) $linkUser->id);
    $linksOpen = $order->status === \App\Models\Order::STATUS_APPROVED
        || ($order->status === \App\Models\Order::STATUS_IN_PRODUCTION && ! $order->productionPlan);
    $hasRequired = $order->items->contains(fn ($i) => $i->measurement_eligibility === 'required');
    $linkContacts = $order->organization->contacts()->where('is_active', true)->orderByDesc('is_primary')->orderBy('first_name')->orderBy('last_name')->get();
    $links = $order->measurementLinks()->with('contact')->get();
@endphp

@if($canManageLinks && $hasRequired)
<section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" id="measurement-links">
    <h2 class="text-lg font-semibold text-slate-900">Customer Measurements</h2>
    <p class="mt-1 text-sm text-slate-600">
        Send a secure link so selected contacts can provide measurements for the items that require them. Links expire after {{ \App\Actions\Measurements\IssueMeasurementLink::EXPIRY_DAYS }} days.
    </p>

    @if($errors->has('measurements'))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first('measurements') }}</div>
    @endif

    @if($linksOpen)
        <form method="POST" action="{{ route('orders.measurement-links.store', $order) }}" class="mt-4" data-send-contacts-form data-submit-spinner data-loading-text="Sending...">
            @csrf
            <div class="space-y-2">
                @forelse($linkContacts as $c)
                    <label class="flex items-center gap-3 text-sm {{ blank($c->email) ? 'text-slate-400' : 'text-slate-800' }}">
                        <input type="checkbox" name="contact_ids[]" value="{{ $c->id }}" data-send-contact @disabled(blank($c->email))>
                        <span>{{ trim($c->first_name . ' ' . $c->last_name) }}@if($c->is_primary) <span class="text-xs text-blue-700">(primary)</span>@endif
                            — {{ $c->email ?: 'no email address' }}</span>
                    </label>
                @empty
                    <p class="text-sm text-slate-600">This organization has no active contacts.</p>
                @endforelse
            </div>
            <p class="mt-3 text-xs text-slate-500" data-send-hint>Select at least one contact with an email address.</p>
            <button type="submit" data-send-submit class="mt-3 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                Send Measurement Link
            </button>
        </form>
    @endif

    @if($links->isNotEmpty())
        <table class="mt-6 w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-500">
                <tr><th class="py-2">Contact</th><th>Sent</th><th>Expires</th><th>Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($links as $l)
                    @php
                        $status = $l->revoked_at ? 'Revoked' : ($l->expires_at->isPast() ? 'Expired' : 'Active');
                    @endphp
                    <tr>
                        <td class="py-2">{{ trim(($l->contact?->first_name ?? '') . ' ' . ($l->contact?->last_name ?? '')) }}</td>
                        <td>{{ $l->sent_at?->format('j M Y H:i') ?? 'Not sent' }}</td>
                        <td>{{ $l->expires_at->format('j M Y') }}</td>
                        <td>{{ $status }}</td>
                        <td class="text-right">
                            @if($status === 'Active')
                                <form method="POST" action="{{ route('orders.measurement-links.revoke', [$order, $l]) }}" class="flex items-center justify-end gap-2">
                                    @csrf
                                    <input type="text" name="reason" required minlength="10" maxlength="2000" placeholder="Reason (min 10 characters)" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                    <button type="submit" class="rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white">Revoke</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endif
