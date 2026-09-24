@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900">Contacts</h1>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                        {{ $contacts->total() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-600">
                    Manage organization contacts and their current information.
                </p>
            </div>

            @can('create', App\Models\Contact::class)
                <a href="{{ route('contacts.create') }}"
                   class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                    Add Contact
                </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('contacts.index') }}" class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search name, organization, phone, email..."
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >

                    <button type="submit"
                            class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                        Search
                    </button>

                    @if($search !== '')
                        <a href="{{ route('contacts.index') }}"
                           class="rounded-lg border border-slate-300 px-5 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Organization</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Phone</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Primary</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($contacts as $contact)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $contact->first_name }} {{ $contact->middle_name }} {{ $contact->last_name }}
                                    </div>
                                    @if($contact->email)
                                        <div class="text-xs text-slate-500">{{ $contact->email }}</div>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <a href="{{ route('organizations.show', $contact->organization) }}"
                                       class="font-medium text-slate-900 hover:text-emerald-700">
                                        {{ $contact->organization->name }}
                                    </a>
                                    <div class="text-xs text-slate-500">
                                        {{ $contact->organization->organization_code }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $contact->position ?: '—' }}
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $contact->phone }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if($contact->is_primary)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Primary
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if($contact->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        @can('view', $contact)
                                            <a href="{{ route('contacts.show', $contact) }}"
                                               class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                View
                                            </a>
                                        @endcan

                                        @can('update', $contact)
                                            <a href="{{ route('contacts.edit', $contact) }}"
                                               class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                Edit
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <p class="text-sm font-medium text-slate-900">No contacts found.</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Add a contact or adjust your search.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($contacts->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $contacts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
