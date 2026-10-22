@extends('layouts.public')

@section('content')
@php $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500'; @endphp

<section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.16),_transparent_38%)]"></div>
    <div class="relative mx-auto max-w-4xl px-6 py-14 sm:px-8">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-400">Order {{ $order->order_number }}</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Provide your measurements</h1>
        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
            Hello {{ $contactName }}, {{ $organization->name }} needs to provide measurements for the items below.
            This link is personal to you and expires on {{ $expiresAt->format('j F Y') }}. All measurements are in inches.
        </p>
    </div>
</section>

<section class="bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-10 sm:px-8">

        @if(session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->has('link') || $errors->has('items'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ $errors->first('link') ?: $errors->first('items') }}
            </div>
        @endif

        @php $anyOpen = collect($states)->contains(fn ($s) => $s['open']); @endphp

        <form method="POST" action="{{ route('measurements.public.store', ['token' => $token]) }}" id="measurement-form" data-submit-spinner data-loading-text="Submitting...">
            @csrf
            @foreach($items as $item)
                @php
                    $id = $item->id;
                    $st = $states[$id];
                    $profile = $st['profile'];
                    $strict = $st['strict'];
                    $qty = (int) round((float) $item->quantity);
                    $method = old("items.$id.method", $profile ? 'individual' : 'size_quantity');
                    $oldPeople = old("items.$id.people");
                    $rows = is_array($oldPeople) ? array_values($oldPeople) : ($st['draftPeople'] ?? []);
                    $hasDraftPeople = !empty($st['draftPeople']) && !is_array($oldPeople);
                    $rowCount = $hasDraftPeople ? count($rows) : max(count($rows), 1);
                    $itemErrors = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, "items.$id"))->flatten()->unique()->values();
                @endphp

                <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                     data-item
                     data-open="{{ $st['open'] ? '1' : '0' }}"
                     data-strict="{{ $strict ? '1' : '0' }}"
                     data-quantity="{{ $qty }}" data-save-sizes-url="{{ route('measurements.public.sizes.store', ['token' => $token, 'item' => $id]) }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg font-semibold text-slate-950">{{ $item->item_name }}</h2>
                        <span class="text-sm text-slate-600">Quantity: {{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }} {{ strtolower($item->unit) }}</span>
                    </div>

                    @if($st['status'] === 'submitted')
                        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Received and under review.</p>
                    @elseif($st['status'] === 'accepted')
                        <p class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">Accepted. Thank you.</p>
                    @else
                        @if($st['reason'])
                            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                Oneyard asked for a correction: {{ $st['reason'] }}
                            </p>
                        @endif
                        @foreach($itemErrors as $msg)
                            <p class="mt-3 text-sm text-red-700">{{ $msg }}</p>
                        @endforeach

                        @if($profile)
                            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-800">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="items[{{ $id }}][method]" value="individual" data-method @checked($method === 'individual')> Enter each person's measurements</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="items[{{ $id }}][method]" value="size_quantity" data-method @checked($method === 'size_quantity')> Choose our commercial sizes</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" value="upload" data-method-upload> Upload a spreadsheet</label>
                            </div>
                            <div data-panel-upload hidden>
                                @include('measurements.public.partials.upload', ['id' => $id, 'token' => $token, 'garment' => $item->product_type])
                            </div>
                        @else
                            <input type="hidden" name="items[{{ $id }}][method]" value="size_quantity">
                            <p class="mt-4 text-sm text-slate-600">Please choose how many you need in each commercial size.</p>
                        @endif

                        @if($profile)
                            <div data-panel="individual" @if($method !== 'individual') hidden @endif>
                                @if($strict)
                                    <p class="mt-4 text-sm text-slate-600">Measurements are needed for exactly {{ $qty }} {{ $qty === 1 ? 'person' : 'people' }}.</p>
                                @endif
                                <div
                                    data-people
                                    data-next="{{ $rowCount }}"
                                    data-strict="{{ $strict ? '1' : '0' }}"
                                    data-max="{{ $strict ? $qty : config('measurements.max_people_per_item') }}"
                                    data-save-url="{{ route('measurements.public.people.store', ['token' => $token, 'item' => $id]) }}"
                                    data-update-url="{{ route('measurements.public.people.update', ['token' => $token, 'item' => $id, 'position' => 999999]) }}"
                                    data-remove-url="{{ route('measurements.public.people.destroy', ['token' => $token, 'item' => $id, 'position' => 999999]) }}"
                                >
                                    @for($i = 0; $i < $rowCount; $i++)
                                        @php
                                            $personValues = $rows[$i] ?? [];
                                            $isSavedPerson = $hasDraftPeople;
                                            if ($isSavedPerson) {
                                                $personValues['position'] = $personValues['position'] ?? ($i + 1);
                                            }
                                        @endphp
                                        @include('measurements.public._person', ['itemId' => $id, 'index' => $i, 'profile' => $profile, 'strict' => $strict, 'values' => $personValues, 'saved' => $isSavedPerson])
                                    @endfor
                                </div>
                                <button
                                    type="button"
                                    data-add-person
                                    class="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 hover:border-amber-500 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span aria-hidden="true" class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-950 text-lg leading-none text-white">+</span>
                                    Add another person
                                </button>
                                <p data-add-person-help class="mt-2 text-xs text-slate-500" aria-live="polite"></p>
                                <template data-person-template>
                                    @include('measurements.public._person', ['itemId' => $id, 'index' => '__INDEX__', 'profile' => $profile, 'strict' => $strict, 'values' => []])
                                </template>
                            </div>
                        @endif

                        <div data-panel="size_quantity" @if($profile && $method !== 'size_quantity') hidden @endif>
                            <p class="mt-4 text-sm text-slate-600">
                                Enter how many you need in each size @if($strict)(the total must be {{ $qty }})@endif.
                            </p>
                            <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-8">
                                @foreach(config('measurements.sizes') as $size)
                                    <label class="block text-xs font-medium text-slate-600">{{ $size }}
                                        <input type="text" inputmode="numeric" name="items[{{ $id }}][sizes][{{ $size }}]" value="{{ old("items.$id.sizes.$size", $st['draftSizes'][$size] ?? '') }}" class="{{ $input }} mt-1">
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($anyOpen)
                <button type="submit" id="measurement-submit" disabled
                        class="rounded-xl bg-slate-950 px-6 py-3.5 text-sm font-bold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                    Submit Measurements
                </button>
                <p data-submit-help class="mt-3 text-sm text-slate-600" aria-live="polite">
                    Complete the required measurements for every item to enable submission.
                </p>
                <p class="mt-2 text-xs text-slate-500">Once submitted, Oneyard will review your measurements. You can only change them if a correction is requested.</p>
            @endif
        </form>
    </div>
</section>
@endsection
