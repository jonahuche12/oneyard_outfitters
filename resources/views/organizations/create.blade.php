@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">

        {{-- Page Header --}}
        <div>
            <a
                href="{{ route('organizations.index') }}"
                class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
            >
                ← Back to Organizations
            </a>

            <div class="mt-4">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Add Organization
                </h1>

                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    Register a school, business, institution or other organization
                    that Oneyard Outfitters may serve.
                </p>
            </div>
        </div>


        {{-- Form --}}
        <form
            method="POST"
            action="{{ route('organizations.store') }}"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf


            {{-- Identity --}}
            <section class="p-6 sm:p-8">

                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Organization Identity
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Identify the organization and classify its operating structure.
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
                            value="{{ old('name') }}"
                            autocomplete="organization"
                            required
                            autofocus
                            placeholder="Enter organization name"
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
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
                            <option value="">Select type</option>
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
                                    @selected(old('type') === $value)
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
                            <option value="">Select ownership</option>
                            @foreach ([
                                'private' => 'Private',
                                'public' => 'Public',
                                'government' => 'Government',
                                'non_profit' => 'Non-profit',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('ownership') === $value)
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
                        Primary contact channels for the organization.
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
                            value="{{ old('phone') }}"
                            autocomplete="tel"
                            placeholder="Phone number"
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
                            value="{{ old('email') }}"
                            autocomplete="email"
                            placeholder="organization@example.com"
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
                            value="{{ old('website') }}"
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
                        Record where the organization operates.
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
                            placeholder="Street address"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >{{ old('address') }}</textarea>

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
                            value="{{ old('area') }}"
                            placeholder="Area or neighbourhood"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
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
                            value="{{ old('city') }}"
                            placeholder="City"
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
                            value="{{ old('lga') }}"
                            placeholder="Local Government Area"
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
                            value="{{ old('state') }}"
                            placeholder="State"
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
                            value="{{ old('country', 'Nigeria') }}"
                            placeholder="Country"
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
                        Record useful context about the organization.
                    </p>
                </div>

                <textarea
                    id="notes"
                    name="notes"
                    rows="5"
                    placeholder="Additional notes..."
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >{{ old('notes') }}</textarea>

                @error('notes')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </section>


            {{-- Form Actions --}}
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">

                <a
                    href="{{ route('organizations.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    Create Organization
                </button>

            </div>

        </form>

    </div>
@endsection
