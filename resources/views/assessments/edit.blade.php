@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Edit Assessment</h1>

            <p class="oy-page-description">
                {{ $assessment->organization->name }}
                <span class="oy-code">({{ $assessment->organization->organization_code }})</span>
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('assessments.show', $assessment) }}" class="oy-btn oy-btn-secondary">
                Cancel
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('assessments.update', $assessment) }}" class="oy-form">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="oy-alert oy-alert-danger mb-6">
                Please correct the highlighted fields and try again.
            </div>
        @endif

        <div class="oy-card">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Assessment Context</h2>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">Organization</label>
                        <input
                            type="text"
                            value="{{ $assessment->organization->name }} — {{ $assessment->organization->organization_code }}"
                            class="oy-input bg-slate-50"
                            disabled
                        >
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Assessment Date <span class="oy-form-required">*</span>
                        </label>
                        <input
                            type="date"
                            name="assessment_date"
                            value="{{ old('assessment_date', $assessment->assessment_date->format('Y-m-d')) }}"
                            class="oy-input @error('assessment_date') is-invalid @enderror"
                            required
                        >
                        @error('assessment_date') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Status <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="status"
                            class="oy-select @error('status') is-invalid @enderror"
                            required
                        >
                            <option value="draft" @selected(old('status', $assessment->status) === 'draft')>
                                Draft
                            </option>

                            <option value="completed" @selected(old('status', $assessment->status) === 'completed')>
                                Completed
                            </option>
                        </select>

                        @error('status') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Assessed By</label>
                        <input
                            type="text"
                            value="{{ $assessment->assessedBy?->name ?? 'Staff member removed' }}"
                            class="oy-input bg-slate-50"
                            disabled
                        >
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Organization Situation</h2>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    @foreach([
                        'needs' => 'Needs',
                        'procurement_process' => 'Procurement Process',
                        'estimated_demand' => 'Estimated Demand',
                    ] as $field => $label)

                        <div class="oy-form-group sm:col-span-2">
                            <label class="oy-form-label">
                                {{ $label }}
                                @if($field === 'needs')
                                    <span class="oy-form-required">*</span>
                                @endif
                            </label>

                            <textarea
                                name="{{ $field }}"
                                class="oy-textarea @error($field) is-invalid @enderror"
                                @if($field === 'needs') required @endif
                            >{{ old($field, $assessment->{$field}) }}</textarea>

                            @error($field) <div class="oy-error">{{ $message }}</div> @enderror
                        </div>

                    @endforeach

                    <div class="oy-form-group">
                        <label class="oy-form-label">Current Supplier</label>
                        <input
                            type="text"
                            name="current_supplier"
                            value="{{ old('current_supplier', $assessment->current_supplier) }}"
                            class="oy-input @error('current_supplier') is-invalid @enderror"
                        >
                        @error('current_supplier') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Decision Maker</label>
                        <input
                            type="text"
                            name="decision_maker"
                            value="{{ old('decision_maker', $assessment->decision_maker) }}"
                            class="oy-input @error('decision_maker') is-invalid @enderror"
                        >
                        @error('decision_maker') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Estimated Budget</label>
                        <input
                            type="number"
                            name="estimated_budget"
                            value="{{ old('estimated_budget', $assessment->estimated_budget) }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('estimated_budget') is-invalid @enderror"
                        >
                        @error('estimated_budget') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Buying Timeline</label>
                        <input
                            type="text"
                            name="buying_timeline"
                            value="{{ old('buying_timeline', $assessment->buying_timeline) }}"
                            class="oy-input @error('buying_timeline') is-invalid @enderror"
                        >
                        @error('buying_timeline') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Assessment Intelligence</h2>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid">

                    @foreach([
                        'observations' => 'Observations',
                        'opportunities' => 'Opportunities',
                        'risks' => 'Risks',
                        'recommendation' => 'Recommendation',
                        'notes' => 'Internal Notes',
                    ] as $field => $label)

                        <div class="oy-form-group">
                            <label class="oy-form-label">{{ $label }}</label>

                            <textarea
                                name="{{ $field }}"
                                class="oy-textarea @error($field) is-invalid @enderror"
                            >{{ old($field, $assessment->{$field}) }}</textarea>

                            @error($field) <div class="oy-error">{{ $message }}</div> @enderror
                        </div>

                    @endforeach

                </div>
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                <a
                    href="{{ route('assessments.show', $assessment) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button type="submit" class="oy-btn oy-btn-primary">
                    Save Assessment
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
