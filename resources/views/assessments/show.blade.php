@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Assessment</h1>

                @if($assessment->status === 'completed')
                    <span class="oy-badge oy-badge-success">Completed</span>
                @else
                    <span class="oy-badge oy-badge-warning">Draft</span>
                @endif
            </div>

            <p class="oy-page-description">
                {{ $assessment->organization->name }}
                <span class="oy-code">({{ $assessment->organization->organization_code }})</span>
            </p>
        </div>

        <div class="oy-page-actions">
            <a
                href="{{ route('organizations.show', $assessment->organization) }}"
                class="oy-btn oy-btn-secondary"
            >
                Organization
            </a>

            @can('update', $assessment)
                <a
                    href="{{ route('assessments.edit', $assessment) }}"
                    class="oy-btn oy-btn-primary"
                >
                    Edit Assessment
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="oy-alert oy-alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="oy-card">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Assessment Context</h2>
        </div>

        <div class="oy-card-body">
            <div class="grid gap-6 sm:grid-cols-3">
                <div>
                    <div class="oy-meta">Organization</div>
                    <div class="mt-1 font-medium text-slate-900">
                        {{ $assessment->organization->name }}
                    </div>
                    <div class="oy-code mt-1">
                        {{ $assessment->organization->organization_code }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Assessment Date</div>
                    <div class="mt-1 font-medium text-slate-900">
                        {{ $assessment->assessment_date->format('d M Y') }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Assessed By</div>
                    <div class="mt-1 font-medium text-slate-900">
                        {{ $assessment->assessedBy?->name ?? 'Staff member removed' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="oy-card-header">
            <h2 class="oy-card-title">Organization Situation</h2>
        </div>

        <div class="oy-card-body">
            <div class="grid gap-6 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <div class="oy-meta">Needs</div>
                    <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $assessment->needs }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Current Supplier</div>
                    <div class="mt-1 text-sm text-slate-800">
                        {{ $assessment->current_supplier ?: '—' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Decision Maker</div>
                    <div class="mt-1 text-sm text-slate-800">
                        {{ $assessment->decision_maker ?: '—' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Estimated Budget</div>
                    <div class="mt-1 text-sm font-medium text-slate-900">
                        {{ $assessment->estimated_budget !== null
                            ? '₦' . number_format((float) $assessment->estimated_budget, 2)
                            : '—' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Buying Timeline</div>
                    <div class="mt-1 text-sm text-slate-800">
                        {{ $assessment->buying_timeline ?: '—' }}
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <div class="oy-meta">Procurement Process</div>
                    <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $assessment->procurement_process ?: '—' }}
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <div class="oy-meta">Estimated Demand</div>
                    <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $assessment->estimated_demand ?: '—' }}
                    </div>
                </div>

            </div>
        </div>

        <div class="oy-card-header">
            <h2 class="oy-card-title">Assessment Intelligence</h2>
        </div>

        <div class="oy-card-body">
            <div class="space-y-6">

                @foreach([
                    'observations' => 'Observations',
                    'opportunities' => 'Opportunities',
                    'risks' => 'Risks',
                    'recommendation' => 'Recommendation',
                    'notes' => 'Internal Notes',
                ] as $field => $label)

                    <div>
                        <div class="oy-meta">{{ $label }}</div>
                        <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ $assessment->{$field} ?: '—' }}
                        </div>
                    </div>

                @endforeach

            </div>
        </div>
    </div>

</div>
@endsection
