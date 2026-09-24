@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('contacts.index') }}"
               class="text-sm font-medium text-slate-600 hover:text-slate-900">
                ← Back to Contacts
            </a>

            <h1 class="mt-3 text-2xl font-semibold text-slate-900">Edit Contact</h1>
            <p class="mt-1 text-sm text-slate-600">
                Update the contact information and organization relationship.
            </p>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium">Please correct the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('contacts.update', $contact) }}"
              class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Organization & Identity</h2>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="organization_id" class="block text-sm font-medium text-slate-700">
                            Organization
                        </label>
                        <select id="organization_id" name="organization_id" required
                                class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">Select organization</option>
                            @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}"
                                    @selected(old('organization_id', $contact->organization_id) == $organization->id)>
                                    {{ $organization->name }} — {{ $organization->organization_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="first_name" class="block text-sm font-medium text-slate-700">First Name</label>
                        <input id="first_name" name="first_name" value="{{ old('first_name', $contact->first_name) }}" required
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-slate-700">Last Name</label>
                        <input id="last_name" name="last_name" value="{{ old('last_name', $contact->last_name) }}" required
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="middle_name" class="block text-sm font-medium text-slate-700">Middle Name</label>
                        <input id="middle_name" name="middle_name" value="{{ old('middle_name', $contact->middle_name) }}"
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="position" class="block text-sm font-medium text-slate-700">Position</label>
                        <input id="position" name="position" value="{{ old('position', $contact->position) }}"
                               placeholder="e.g. Principal, Director, Procurement Officer"
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Communication</h2>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone', $contact->phone) }}" required
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="alternate_phone" class="block text-sm font-medium text-slate-700">Alternate Phone</label>
                        <input id="alternate_phone" name="alternate_phone" value="{{ old('alternate_phone', $contact->alternate_phone) }}"
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $contact->email) }}"
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-slate-700">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $contact->whatsapp) }}"
                               class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Contact Settings</h2>

                <label class="mt-5 flex items-start gap-3">
                    <input type="hidden" name="is_primary" value="0">
                    <input type="checkbox" name="is_primary" value="1"
                           @checked(old('is_primary', $contact->is_primary))
                           class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Primary contact</span>
                        <span class="block text-xs text-slate-500">
                            Making this contact primary will remove primary status from the organization's current primary contact.
                        </span>
                    </span>
                </label>

                <div class="mt-5">
                    <label for="notes" class="block text-sm font-medium text-slate-700">Current Notes</label>
                    <textarea id="notes" name="notes" rows="4"
                              class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes', $contact->notes) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('contacts.index') }}"
                   class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection
