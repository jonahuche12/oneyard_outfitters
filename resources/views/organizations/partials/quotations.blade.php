            @if($organizationQuotations->isEmpty())
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    No quotations have been generated for this organization yet.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="oy-table min-w-[760px]">
                        <thead>
                            <tr>
                                <th>Quotation</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($organizationQuotations as $quotation)
                                <tr>
                                    <td class="font-medium text-slate-900">
                                        {{ $quotation->quotation_number }}
                                    </td>

                                    <td>
                                        {{ optional($quotation->quotation_date)->format('d M Y') }}
                                    </td>

                                    <td>
                                        {{ $quotation->valid_until
                                            ? $quotation->valid_until->format('d M Y')
                                            : '—' }}
                                    </td>

                                    <td>
                                        <span class="oy-badge oy-badge-secondary">
                                            {{ ucfirst($quotation->status) }}
                                        </span>
                                    </td>

                                    <td class="font-medium">
                                        ₦{{ number_format((float) $quotation->total, 2) }}
                                    </td>

                                    <td class="text-right">
                                        @can('view', $quotation)
                                            <a
                                                href="{{ route('quotations.show', $quotation) }}"
                                                class="oy-btn oy-btn-secondary oy-btn-sm"
                                            >
                                                View
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($organizationQuotations->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">
                        {{ $organizationQuotations->links() }}
                    </div>
                @endif
            @endif
