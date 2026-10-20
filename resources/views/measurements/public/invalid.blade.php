@extends('layouts.public')

@section('content')
<section class="bg-slate-50">
    <div class="mx-auto max-w-xl px-6 py-24 text-center">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold text-slate-950">This link is not valid</h1>
            <p class="mt-3 text-base leading-7 text-slate-600">
                The link may have expired or been replaced. Please contact Oneyard Outfitters to request a new one.
            </p>
            <a href="{{ url('/contact') }}" class="mt-6 inline-flex rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">
                Contact Us
            </a>
        </div>
    </div>
</section>
@endsection
