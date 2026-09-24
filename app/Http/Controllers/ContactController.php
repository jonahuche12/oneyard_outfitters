<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contact\StoreContactRequest;
use App\Http\Requests\Contact\UpdateContactRequest;
use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Contact::class);

        $search = trim((string) $request->input('search'));

        $contacts = Contact::query()
            ->with('organization')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('alternate_phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhereHas('organization', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('organization_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('contacts.index', [
            'contacts' => $contacts,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Contact::class);

        $organizations = Organization::query()
            ->orderBy('name')
            ->get(['id', 'organization_code', 'name']);

        $organizationId = $request->integer('organization_id');

        return view('contacts.create', [
            'organizations' => $organizations,
            'organizationId' => $organizationId,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        Gate::authorize('create', Contact::class);

        $validated = $request->validated();

        $contact = DB::transaction(function () use ($validated) {
            $isPrimary = (bool) ($validated['is_primary'] ?? false);

            if ($isPrimary) {
                Contact::query()
                    ->where('organization_id', $validated['organization_id'])
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            return Contact::create([
                ...$validated,
                'is_primary' => $isPrimary,
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('contacts.show', $contact)
            ->with('success', 'Contact created successfully.');
    }

    public function show(Contact $contact): View
    {
        Gate::authorize('view', $contact);

        $contact->load([
            'organization',
            'interactionNotes' => fn ($query) => $query
                ->with('recorder')
                ->latest(),
        ]);

        return view('contacts.show', [
            'contact' => $contact,
        ]);
    }

    public function edit(Contact $contact): View
    {
        Gate::authorize('update', $contact);

        $organizations = Organization::query()
            ->orderBy('name')
            ->get(['id', 'organization_code', 'name']);

        return view('contacts.edit', [
            'contact' => $contact,
            'organizations' => $organizations,
        ]);
    }

    public function update(
        UpdateContactRequest $request,
        Contact $contact
    ): RedirectResponse {
        Gate::authorize('update', $contact);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $contact) {
            $isPrimary = (bool) ($validated['is_primary'] ?? false);

            if ($isPrimary) {
                Contact::query()
                    ->where('organization_id', $validated['organization_id'])
                    ->where('id', '!=', $contact->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $contact->update([
                ...$validated,
                'is_primary' => $isPrimary,
            ]);
        });

        return redirect()
            ->route('contacts.show', $contact)
            ->with('success', 'Contact updated successfully.');
    }

    public function activate(Contact $contact): RedirectResponse
    {
        Gate::authorize('activate', $contact);

        $contact->update([
            'is_active' => true,
        ]);

        return redirect()
            ->route('contacts.show', $contact)
            ->with('success', 'Contact activated successfully.');
    }

    public function deactivate(Contact $contact): RedirectResponse
    {
        Gate::authorize('deactivate', $contact);

        $contact->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('contacts.show', $contact)
            ->with('success', 'Contact deactivated successfully.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        Gate::authorize('delete', $contact);

        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }
}
