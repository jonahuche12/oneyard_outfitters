<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactNote\StoreContactNoteRequest;
use App\Http\Requests\ContactNote\UpdateContactNoteRequest;
use App\Models\Contact;
use App\Models\ContactNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContactNoteController extends Controller
{
    public function create(Contact $contact): View
    {
        Gate::authorize('view', $contact);

        Gate::authorize('create', ContactNote::class);

        return view('contact-notes.create', [
            'contact' => $contact->load('organization'),
        ]);
    }

    public function store(
        StoreContactNoteRequest $request,
        Contact $contact
    ): RedirectResponse {
        Gate::authorize('view', $contact);

        Gate::authorize('create', ContactNote::class);

        ContactNote::create([
            'contact_id' => $contact->id,
            'recorded_by' => $request->user()->id,
            'note' => $request->validated()['note'],
        ]);

        return redirect()
            ->route('contacts.show', $contact)
            ->with('success', 'Contact interaction recorded successfully.');
    }

    public function edit(ContactNote $contactNote): View
    {
        Gate::authorize('update', $contactNote);

        $contactNote->load([
            'contact.organization',
            'recorder',
        ]);

        return view('contact-notes.edit', [
            'contactNote' => $contactNote,
        ]);
    }

    public function update(
        UpdateContactNoteRequest $request,
        ContactNote $contactNote
    ): RedirectResponse {
        Gate::authorize('update', $contactNote);

        $contactNote->update([
            'note' => $request->validated()['note'],
        ]);

        return redirect()
            ->route('contacts.show', $contactNote->contact_id)
            ->with('success', 'Contact interaction updated successfully.');
    }

    public function destroy(ContactNote $contactNote): RedirectResponse
    {
        Gate::authorize('delete', $contactNote);

        $contactId = $contactNote->contact_id;

        $contactNote->delete();

        return redirect()
            ->route('contacts.show', $contactId)
            ->with('success', 'Contact interaction deleted successfully.');
    }
}
