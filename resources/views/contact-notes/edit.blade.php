@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('contacts.show', $contactNote->contact) }}"
               class="text-sm text-slate-600 hover:text-slate-900">
                ← Back to Contact
            </a>

            <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                Edit Interaction Record
            </h1>

            <p class="mt-1 text-sm text-slate-600">
                {{ $contactNote->contact->first_name }}
                {{ $contactNote->contact->last_name }}
                · {{ $contactNote->contact->organization->name }}
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST"
                  action="{{ route('contact-notes.update', $contactNote) }}"
                  class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="note" class="block text-sm font-medium text-slate-700">
                        Interaction Note
                    </label>

                    <textarea
                        id="note"
                        name="note"
                        rows="8"
                        required
                        class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >{{ old('note', $contactNote->note) }}</textarea>
                </div>

                <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                    Originally recorded by
                    <span class="font-medium text-slate-800">
                        {{ $contactNote->recorder?->name ?? 'Former staff member' }}
                    </span>
                    on
                    <span class="font-medium text-slate-800">
                        {{ $contactNote->created_at?->format('d M Y, H:i') }}
                    </span>.
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('contacts.show', $contactNote->contact) }}"
                       class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
