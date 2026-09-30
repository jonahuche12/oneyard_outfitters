@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">

        {{-- Page Header --}}
        <div>
            <a
                href="{{ route('organizations.show', $organization) }}"
                class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
            >
                ← Back to {{ $organization->name }}
            </a>

            <div class="mt-4">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Edit Organization
                </h1>

                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    Update the organization's information. Its organization code remains
                    stable once assigned.
                </p>
            </div>
        </div>


        {{-- Identity Reference --}}
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Organization Code
                    </div>

                    <div class="mt-1 font-mono text-sm font-semibold text-slate-900">
                        {{ $organization->organization_code }}
                    </div>
                </div>

                <div>
                    @if ($organization->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </section>


        {{-- Form --}}
        <form
            method="POST"
            action="{{ route('organizations.update', $organization) }}"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf
            @method('PUT')


            {{-- Identity --}}
            <section class="p-6 sm:p-8">

                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Organization Identity
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Update the organization's classification and name.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <label
                            for="name"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Organization name
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $organization->name) }}"
                            autocomplete="organization"
                            required
                            autofocus
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label
                            for="type"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Organization type
                        </label>

                        <select
                            id="type"
                            name="type"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                            @foreach ([
                                'school' => 'School',
                                'company' => 'Company',
                                'church' => 'Church',
                                'ngo' => 'NGO',
                                'government' => 'Government',
                                'association' => 'Association',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('type', $organization->type) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('type')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label
                            for="ownership"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Ownership
                        </label>

                        <select
                            id="ownership"
                            name="ownership"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                            @foreach ([
                                'private' => 'Private',
                                'public' => 'Public',
                                'government' => 'Government',
                                'non_profit' => 'Non-profit',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('ownership', $organization->ownership) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('ownership')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

            </section>


            {{-- Contact Information --}}
            <section class="border-t border-slate-200 p-6 sm:p-8">

                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Contact Information
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Update the organization's contact channels.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">
                            Phone
                        </label>

                        <input
                            id="phone"
                            type="text"
                            name="phone"
                            value="{{ old('phone', $organization->phone) }}"
                            autocomplete="tel"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
                            Email address
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $organization->email) }}"
                            autocomplete="email"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div class="sm:col-span-2">
                        <label for="website" class="mb-2 block text-sm font-medium text-slate-700">
                            Website
                        </label>

                        <input
                            id="website"
                            type="url"
                            name="website"
                            value="{{ old('website', $organization->website) }}"
                            placeholder="https://example.com"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('website')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

            </section>


            {{-- Location --}}
            <section class="border-t border-slate-200 p-6 sm:p-8">

                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Location
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Update the organization's operating location.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <label for="address" class="mb-2 block text-sm font-medium text-slate-700">
                            Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >{{ old('address', $organization->address) }}</textarea>

                        @error('address')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label for="area" class="mb-2 block text-sm font-medium text-slate-700">
                            Area / Neighbourhood
                        </label>

                        <input
                            id="area"
                            type="text"
                            name="area"
                            value="{{ old('area', $organization->area) }}"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('area')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label for="city" class="mb-2 block text-sm font-medium text-slate-700">
                            City
                        </label>

                        <input
                            id="city"
                            type="text"
                            name="city"
                            value="{{ old('city', $organization->city) }}"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('city')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label for="lga" class="mb-2 block text-sm font-medium text-slate-700">
                            LGA
                        </label>

                        <input
                            id="lga"
                            type="text"
                            name="lga"
                            value="{{ old('lga', $organization->lga) }}"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('lga')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label for="state" class="mb-2 block text-sm font-medium text-slate-700">
                            State
                        </label>

                        <input
                            id="state"
                            type="text"
                            name="state"
                            value="{{ old('state', $organization->state) }}"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('state')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label for="country" class="mb-2 block text-sm font-medium text-slate-700">
                            Country
                        </label>

                        <input
                            id="country"
                            type="text"
                            name="country"
                            value="{{ old('country', $organization->country ?: 'Nigeria') }}"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('country')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

            </section>


            {{-- Notes --}}
            <section class="border-t border-slate-200 p-6 sm:p-8">

                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Notes
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Update useful context about this organization.
                    </p>
                </div>

                <textarea
                    id="notes"
                    name="notes"
                    rows="5"
                    placeholder="Additional notes..."
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >{{ old('notes', $organization->notes) }}</textarea>

                @error('notes')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </section>


            {{-- Form Actions --}}
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">

                <a
                    href="{{ route('organizations.show', $organization) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="oy-btn oy-btn-primary focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>
@endsection
