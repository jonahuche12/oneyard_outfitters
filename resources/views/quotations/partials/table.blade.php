<div class="oy-table-wrapper">

    {{-- Read by resources/js/ajax-list.js to keep the header count in sync. --}}
    <div data-list-total="{{ $quotations->total() }}" hidden></div>

    <div class="oy-card-header">
        <h2 class="oy-card-title">Quotation Records</h2>
        <p class="oy-card-description">
            Follow each commercial offer from draft to customer response.
        </p>
    </div>

    <div class="oy-table-scroll">
        <table class="oy-table">
            <thead>
                <tr>
                    <th scope="col">Quotation</th>
                    <th scope="col">Organization</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Date</th>
                    <th scope="col">Delivery</th>
                    <th scope="col">Status</th>
                    <th scope="col">Total</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($quotations as $quotation)
                    <tr data-quotation-row="{{ $quotation->id }}">
                        <td>
                            <div class="oy-table-primary">{{ $quotation->quotation_number }}</div>
                            <div class="oy-table-secondary">
                                {{ $quotation->items_count }}
                                {{ \Illuminate\Support\Str::plural('item', $quotation->items_count) }}
                            </div>
                        </td>

                        <td>
                            @if ($quotation->organization)
                                @can('view', $quotation->organization)
                                    <a
                                        href="{{ route('organizations.show', $quotation->organization) }}"
                                        class="oy-table-primary hover:underline"
                                    >
                                        {{ $quotation->organization->name }}
                                    </a>
                                @else
                                    <span class="oy-table-primary">{{ $quotation->organization->name }}</span>
                                @endcan

                                <div class="oy-code">{{ $quotation->organization->organization_code }}</div>
                            @else
                                <span class="oy-table-secondary">Organization removed</span>
                            @endif
                        </td>

                        <td>
                            @if ($quotation->contact)
                                {{ trim($quotation->contact->first_name . ' ' . $quotation->contact->last_name) }}
                            @else
                                <span class="oy-table-secondary">—</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap">
                            <div>{{ $quotation->quotation_date?->format('d M Y') ?? '—' }}</div>

                            @if ($quotation->valid_until)
                                <div class="oy-table-secondary">
                                    Valid until {{ $quotation->valid_until->format('d M Y') }}
                                </div>
                            @endif
                        </td>

                        <td class="whitespace-nowrap">
                            {{ $quotation->expected_delivery_days }} days
                        </td>

                        <td class="whitespace-nowrap">
                            @include('quotations.partials.status-badge', [
                                'quotationStatus' => $quotation->status,
                            ])
                        </td>

                        <td class="whitespace-nowrap font-medium text-slate-900">
                            ₦{{ number_format((float) $quotation->total, 2) }}
                        </td>

                        <td>
                            <div class="oy-action-group">
                                @can('view', $quotation)
                                    <a
                                        href="{{ route('quotations.show', $quotation) }}"
                                        class="oy-btn oy-btn-secondary oy-btn-sm"
                                    >
                                        View
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="oy-empty-state">
                                <div class="oy-empty-state-title">No quotations found.</div>

                                @if ($search !== '' || $status)
                                    <p class="oy-empty-state-description">
                                        Try another search or clear the current filters.
                                    </p>

                                    <button
                                        type="button"
                                        data-list-clear
                                        class="oy-btn oy-btn-secondary oy-btn-sm mt-4"
                                    >
                                        Clear search
                                    </button>
                                @else
                                    <p class="oy-empty-state-description">
                                        Open an organization account and choose Create Quotation to prepare the first one.
                                    </p>

                                    @can('viewAny', App\Models\Organization::class)
                                        <a
                                            href="{{ route('organizations.index') }}"
                                            class="oy-btn oy-btn-primary oy-btn-sm mt-4"
                                        >
                                            Open Organizations
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($quotations->hasPages())
        <div class="oy-card-footer">
            {{ $quotations->links() }}
        </div>
    @endif

</div>
