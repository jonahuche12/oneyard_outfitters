<?php

namespace App\Http\Controllers;

use App\Http\Requests\FollowUp\StoreFollowUpRequest;
use App\Http\Requests\FollowUp\UpdateFollowUpRequest;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', FollowUp::class);

        $search = trim((string) $request->input('search'));

        $followUps = FollowUp::query()
            ->with(['organization', 'contact', 'recordedBy'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('subject', 'like', "%{$search}%")
                        ->orWhere('outcome', 'like', "%{$search}%")
                        ->orWhere('next_action', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('organization', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere(
                                    'organization_code',
                                    'like',
                                    "%{$search}%"
                                );
                        })
                        ->orWhereHas('contact', function ($query) use ($search) {
                            $query
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('follow_up_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('follow-ups.index', [
            'followUps' => $followUps,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', FollowUp::class);

        $organizationId = $request->integer('organization_id');
        $contactId = $request->integer('contact_id');

        $contact = null;

        if ($contactId > 0) {
            $contact = Contact::query()
                ->with('organization')
                ->findOrFail($contactId);

            $organizationId = $contact->organization_id;
        }

        $organizations = Organization::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'organization_code',
                'name',
            ]);

        $contacts = collect();

        if ($organizationId > 0) {
            $contacts = Contact::query()
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return view('follow-ups.create', [
            'organizations' => $organizations,
            'contacts' => $contacts,
            'organizationId' => $organizationId,
            'contactId' => $contactId,
            'selectedContact' => $contact,
        ]);
    }

    public function store(StoreFollowUpRequest $request): RedirectResponse
    {
        Gate::authorize('create', FollowUp::class);

        $validated = $request->validated();

        if (! empty($validated['contact_id'])) {
            $contact = Contact::findOrFail($validated['contact_id']);

            abort_unless(
                (int) $contact->organization_id === (int) $validated['organization_id'],
                422,
                'The selected contact does not belong to the selected organization.'
            );
        }

        $followUp = FollowUp::create([
            ...$validated,
            'recorded_by' => $request->user()->id,
            'status' => 'open',
        ]);

        return redirect()
            ->route('follow-ups.show', $followUp)
            ->with('success', 'Follow-up recorded successfully.');
    }

    public function show(FollowUp $followUp): View
    {
        Gate::authorize('view', $followUp);

        $followUp->load([
            'organization',
            'contact',
            'recordedBy',
        ]);

        return view('follow-ups.show', [
            'followUp' => $followUp,
        ]);
    }

    public function edit(FollowUp $followUp): View
    {
        Gate::authorize('update', $followUp);

        $followUp->load([
            'organization',
            'contact',
            'recordedBy',
        ]);

        return view('follow-ups.edit', [
            'followUp' => $followUp,
        ]);
    }

    public function update(
        UpdateFollowUpRequest $request,
        FollowUp $followUp
    ): RedirectResponse {
        Gate::authorize('update', $followUp);

        $followUp->update(
            $request->validated()
        );

        return redirect()
            ->route('follow-ups.show', $followUp)
            ->with('success', 'Follow-up updated successfully.');
    }
}
