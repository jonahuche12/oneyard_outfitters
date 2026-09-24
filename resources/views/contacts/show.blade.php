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

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Follow-ups
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Historical interactions and next actions involving this contact.
                    </p>
                </div>

                @can('create', App\Models\FollowUp::class)
                    <a
                        href="{{ route('follow-ups.create', ['contact_id' => $contact->id]) }}"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                    >
                        Record Follow-up
                    </a>
                @endcan
            </div>

            @php
                $contactFollowUps = $contact->followUps()
                    ->with('recordedBy')
                    ->latest('follow_up_date')
                    ->latest('id')
                    ->limit(5)
                    ->get();
            @endphp

            @if($contactFollowUps->isNotEmpty())
                <div class="divide-y divide-slate-200">
                    @foreach($contactFollowUps as $followUp)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @can('view', $followUp)
                                        <a
                                            href="{{ route('follow-ups.show', $followUp) }}"
                                            class="font-medium text-slate-900 hover:text-emerald-700"
                                        >
                                            {{ $followUp->subject }}
                                        </a>
                                    @else
                                        <span class="font-medium text-slate-900">
                                            {{ $followUp->subject }}
                                        </span>
                                    @endcan

                                    @if($followUp->status === 'completed')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Completed
                                        </span>
                                    @elseif($followUp->status === 'cancelled')
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                            Cancelled
                                        </span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                            Open
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">
                                    {{ $followUp->outcome }}
                                </p>

                                <div class="mt-2 text-xs text-slate-500">
                                    {{ ucfirst($followUp->type) }}
                                    <span class="mx-1 text-slate-300">•</span>
                                    {{ $followUp->follow_up_date->format('d M Y, h:i A') }}
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                @can('view', $followUp)
                                    <a
                                        href="{{ route('follow-ups.show', $followUp) }}"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        View
                                    </a>
                                @endcan

                                @can('update', $followUp)
                                    <a
                                        href="{{ route('follow-ups.edit', $followUp) }}"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-900">
                        No follow-ups recorded.
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Record the first interaction with this contact.
                    </p>

                    @can('create', App\Models\FollowUp::class)
                        <a
                            href="{{ route('follow-ups.create', ['contact_id' => $contact->id]) }}"
                            class="mt-4 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800"
                        >
                            Record First Follow-up
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
@endsection
