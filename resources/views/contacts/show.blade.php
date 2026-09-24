@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('contacts.index') }}"
                   class="text-sm font-medium text-slate-600 hover:text-slate-900">
                    ← Back to Contacts
                </a>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900">
                        {{ $contact->first_name }} {{ $contact->middle_name }} {{ $contact->last_name }}
                    </h1>

                    @if($contact->is_primary)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                            Primary Contact
                        </span>
                    @endif

                    @if($contact->is_active)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                            Active
                        </span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                            Inactive
                        </span>
                    @endif
                </div>

                <p class="mt-1 text-sm text-slate-600">
                    {{ $contact->position ?: 'Organization contact' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('update', $contact)
                    <a href="{{ route('contacts.edit', $contact) }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Edit
                    </a>
                @endcan

                @can('activate', $contact)
                    @if(!$contact->is_active)
                        <form method="POST" action="{{ route('contacts.activate', $contact) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Activate
                            </button>
                        </form>
                    @endif
                @endcan

                @can('deactivate', $contact)
                    @if($contact->is_active)
                        <form method="POST" action="{{ route('contacts.deactivate', $contact) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                                Deactivate
                            </button>
                        </form>
                    @endif
                @endcan

                @can('delete', $contact)
                    <form method="POST" action="{{ route('contacts.destroy', $contact) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                                onclick="return confirm('Delete this contact?')">
                            Delete
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Organization</h2>

                <div class="mt-5">
                    <a href="{{ route('organizations.show', $contact->organization) }}"
                       class="font-medium text-slate-900 hover:text-emerald-700">
                        {{ $contact->organization->name }}
                    </a>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $contact->organization->organization_code }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Identity</h2>

                <dl class="mt-5 space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Full Name</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $contact->first_name }} {{ $contact->middle_name }} {{ $contact->last_name }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Position</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $contact->position ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Communication</h2>

                <dl class="mt-5 space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $contact->phone }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Alternate Phone</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $contact->alternate_phone ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $contact->email ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">WhatsApp</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $contact->whatsapp ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Current Notes</h2>

                <div class="mt-5 text-sm leading-6 text-slate-700">
                    @if($contact->notes)
                        {!! nl2br(e($contact->notes)) !!}
                    @else
                        <span class="text-slate-400">No current notes recorded.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Contact Activity</h2>
            <p class="mt-1 text-sm text-slate-500">
                Historical interaction notes will be managed here through the Contact Notes module.
            </p>
        </div>
    </div>
@endsection
