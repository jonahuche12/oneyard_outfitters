<?php

namespace App\Http\Controllers;

use App\Actions\Quotations\CalculateQuotationTotals;
use App\Actions\Quotations\CreateQuotation;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\ProductSpecification;
use App\Models\Quotation;
use App\Models\QuotationRecipient;
use App\Mail\QuotationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Quotation::class);

        $quotations = Quotation::query()
            ->with(['organization', 'contact'])
            ->latest('quotation_date')
            ->latest('id')
            ->paginate(20);

        return view('quotations.index', compact('quotations'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Quotation::class);

        if (! $request->filled('organization_id')) {
            abort(404, 'An organization is required to create a quotation.');
        }

        $organization = Organization::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('organization_id'));

        $contacts = Contact::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $productSpecifications = ProductSpecification::query()
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['draft', 'confirmed'])
            ->latest('specification_date')
            ->latest('id')
            ->get();

        return view('quotations.create', compact(
            'organization',
            'contacts',
            'productSpecifications'
        ));
    }

    public function store(
        Request $request,
        CreateQuotation $createQuotation
    ): RedirectResponse {
        Gate::authorize('create', Quotation::class);

        $validated = $request->validate([
            'organization_id' => [
                'required',
                'exists:organizations,id,is_active,1',
            ],
            'contact_id' => [
                'nullable',
                'exists:contacts,id',
            ],
            'quotation_date' => [
                'required',
                'date',
            ],
            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:quotation_date',
            ],
            'expected_delivery_days' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'additional_charges' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'terms' => [
                'nullable',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'product_specification_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'product_specification_ids.*' => [
                'integer',
                'distinct',
                'exists:product_specifications,id',
            ],
            'quantities' => [
                'required',
                'array',
            ],
            'quantities.*' => [
                'numeric',
                'min:0.01',
            ],
        ]);

        if (! empty($validated['contact_id'])) {
            $contactBelongsToOrganization = Contact::query()
                ->whereKey($validated['contact_id'])
                ->where('organization_id', $validated['organization_id'])
                ->where('is_active', true)
                ->exists();

            if (! $contactBelongsToOrganization) {
                return back()
                    ->withErrors([
                        'contact_id' =>
                            'The selected contact does not belong to the selected organization.',
                    ])
                    ->withInput();
            }
        }

        $specificationIds = collect(
            $validated['product_specification_ids']
        )->map(fn ($id) => (int) $id);

        $specifications = ProductSpecification::query()
            ->where('organization_id', $validated['organization_id'])
            ->whereIn('status', ['draft', 'confirmed'])
            ->whereIn('id', $specificationIds)
            ->get()
            ->keyBy('id');

        if ($specifications->count() !== $specificationIds->count()) {
            return back()
                ->withErrors([
                    'product_specification_ids' =>
                        'One or more selected product specifications are invalid for this organization.',
                ])
                ->withInput();
        }

        $items = [];

        foreach ($specificationIds as $index => $specificationId) {
            $specification = $specifications->get($specificationId);
            $quantity = $validated['quantities'][$specificationId] ?? null;

            if ($quantity === null) {
                return back()
                    ->withErrors([
                        "quantities.{$specificationId}" =>
                            'Enter a quantity for every selected product specification.',
                    ])
                    ->withInput();
            }

            $items[] = [
                'product_specification_id' => $specification->id,
                'item_name' => $specification->item_name,
                'description' => $specification->description,
                'quantity' => (float) $quantity,
                'unit' => $specification->unit ?: 'piece',

                // Snapshot the current Product Specification price into the quotation.
                // The quotation item becomes commercially independent from future
                // Product Specification price changes.
                'unit_price' => (float) $specification->unit_price,
                'line_total' => (float) $quantity * (float) $specification->unit_price,

                'sort_order' => $index,
            ];
        }

        $validated['created_by'] = auth()->id();
        $validated['status'] = Quotation::STATUS_DRAFT;

        unset(
            $validated['product_specification_ids'],
            $validated['quantities']
        );

        $quotation = $createQuotation->execute(
            $validated,
            $items
        );

        return redirect()
            ->route('quotations.show', $quotation)
            ->with(
                'status',
                'Quotation draft created successfully using the current Product Specification prices.'
            );
    }

    public function organizationOptions(Organization $organization)
    {
        Gate::authorize('view', $organization);

        $contacts = Contact::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'position',
                'phone',
            ])
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => trim(implode(' ', array_filter([
                    $contact->first_name,
                    $contact->middle_name,
                    $contact->last_name,
                ]))),
                'position' => $contact->position,
                'phone' => $contact->phone,
                'is_primary' => (bool) $contact->is_primary,
            ])
            ->values();

        $productSpecifications = ProductSpecification::query()
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['draft', 'confirmed'])
            ->latest('specification_date')
            ->latest('id')
            ->get([
                'id',
                'item_name',
                'product_type',
                'description',
                'unit',
                'unit_price',
                'status',
            ])
            ->map(fn (ProductSpecification $specification) => [
                'id' => $specification->id,
                'item_name' => $specification->item_name,
                'product_type' => $specification->product_type,
                'description' => $specification->description,
                'unit' => $specification->unit,
                'unit_price' => $specification->unit_price,
                'status' => $specification->status,
            ])
            ->values();

        return response()->json([
            'contacts' => $contacts,
            'product_specifications' => $productSpecifications,
        ]);
    }

    public function show(Quotation $quotation): View
    {
        Gate::authorize('view', $quotation);

        $quotation->load([
            'organization',
            'contact',
            'createdBy',
            'items.productSpecification',
        ]);

        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation): View
    {
        Gate::authorize('update', $quotation);

        $quotation->load([
            'organization',
            'contact',
            'items',
        ]);

        return view('quotations.edit', compact('quotation'));
    }

    public function update(
        Request $request,
        Quotation $quotation,
        CalculateQuotationTotals $calculateQuotationTotals
    ): RedirectResponse {
        Gate::authorize('update', $quotation);

        $validated = $request->validate([
            'quotation_date' => ['required', 'date'],
            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:quotation_date',
            ],
            'expected_delivery_days' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'additional_charges' => ['nullable', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $quotation->update($validated);

        $calculateQuotationTotals->execute($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'Quotation updated successfully.');
    }

    public function send(Quotation $quotation): RedirectResponse
    {
        Gate::authorize('send', $quotation);

        $quotation->update([
            'status' => Quotation::STATUS_SENT,
        ]);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'Quotation sent successfully.');
    }

    public function cancel(Quotation $quotation): RedirectResponse
    {
        Gate::authorize('cancel', $quotation);

        $quotation->update([
            'status' => Quotation::STATUS_CANCELLED,
        ]);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'Quotation cancelled successfully.');
    }

    public function sendToContacts(
        Request $request,
        Quotation $quotation
    ): RedirectResponse {
        Gate::authorize('send', $quotation);

        if (! in_array($quotation->status, [
            Quotation::STATUS_DRAFT,
            Quotation::STATUS_SENT,
        ], true)) {
            return back()->withErrors([
                'quotation' => 'This quotation cannot be sent in its current status.',
            ]);
        }

        $validated = $request->validate([
            'contact_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'contact_ids.*' => [
                'integer',
                'distinct',
                'exists:contacts,id',
            ],
        ]);

        $contacts = Contact::query()
            ->where('organization_id', $quotation->organization_id)
            ->where('is_active', true)
            ->whereIn('id', $validated['contact_ids'])
            ->get();

        if ($contacts->count() !== count($validated['contact_ids'])) {
            return back()
                ->withErrors([
                    'contact_ids' =>
                        'One or more selected contacts are invalid for this organization.',
                ])
                ->withInput();
        }

        $contactsWithoutEmail = $contacts->filter(
            fn (Contact $contact): bool => blank($contact->email)
        );

        if ($contactsWithoutEmail->isNotEmpty()) {
            return back()
                ->withErrors([
                    'contact_ids' =>
                        'Every selected contact must have an email address before the quotation can be sent.',
                ])
                ->withInput();
        }

        foreach ($contacts as $contact) {
            $recipient = QuotationRecipient::query()->firstOrCreate(
                [
                    'quotation_id' => $quotation->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'email' => $contact->email,
                    'access_token' => bin2hex(random_bytes(32)),
                    'response_status' => 'pending',
                ]
            );

            try {
                Mail::to($recipient->email)
                    ->send(new QuotationInvitation($recipient->load([
                        'quotation',
                        'contact',
                    ])));
            } catch (TransportExceptionInterface $exception) {
                Log::error('Quotation email delivery failed.', [
                    'quotation_id' => $quotation->id,
                    'quotation_number' => $quotation->quotation_number,
                    'recipient_id' => $recipient->id,
                    'email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ]);

                return back()
                    ->withErrors([
                        'quotation' =>
                            'The quotation could not be sent because the email service is currently unavailable. Please try again later.',
                    ])
                    ->withInput();
            }

            $recipient->update([
                'email' => $contact->email,
                'sent_at' => now(),
            ]);
        }

        $quotation->update([
            'status' => Quotation::STATUS_SENT,
        ]);

        return back()->with(
            'status',
            'Quotation sent successfully to the selected contacts.'
        );
    }

}
