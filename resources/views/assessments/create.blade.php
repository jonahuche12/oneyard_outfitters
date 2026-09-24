@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">New Assessment</h1>
            <p class="oy-page-description">
                Record the current commercial situation, needs, buying process and opportunities for an organization.
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('assessments.index') }}" class="oy-btn oy-btn-secondary">
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('assessments.store') }}" class="oy-form">
        @csrf

        @if($errors->any())
            <div class="oy-alert oy-alert-danger mb-6">
                Please correct the highlighted fields and try again.
            </div>
        @endif

        <div class="oy-card">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Assessment Context</h2>
                <p class="oy-card-description">
                    Identify the organization and date of this assessment.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Organization <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="organization_id"
                            class="oy-select @error('organization_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Select organization</option>

                            @foreach($organizations as $organization)
                                <option
                                    value="{{ $organization->id }}"
                                    @selected(old('organization_id', $organizationId) == $organization->id)
                                >
                                    {{ $organization->name }} — {{ $organization->organization_code }}
                                </option>
                            @endforeach
                        </select>

                        @error('organization_id')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Assessment Date <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="date"
                            name="assessment_date"
                            value="{{ old('assessment_date', now()->format('Y-m-d')) }}"
                            class="oy-input @error('assessment_date') is-invalid @enderror"
                            required
                        >

                        @error('assessment_date')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Organization Situation</h2>
                <p class="oy-card-description">
                    Capture what the organization needs and how it currently procures.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">
                            Needs <span class="oy-form-required">*</span>
                        </label>
                        <textarea
                            name="needs"
                            class="oy-textarea @error('needs') is-invalid @enderror"
                            required
                            placeholder="What does the organization currently need?"
                        >{{ old('needs') }}</textarea>
                        @error('needs') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Current Supplier</label>
                        <input
                            type="text"
                            name="current_supplier"
                            value="{{ old('current_supplier') }}"
                            class="oy-input @error('current_supplier') is-invalid @enderror"
                        >
                        @error('current_supplier') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Decision Maker</label>
                        <input
                            type="text"
                            name="decision_maker"
                            value="{{ old('decision_maker') }}"
                            class="oy-input @error('decision_maker') is-invalid @enderror"
                        >
                        @error('decision_maker') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Estimated Budget</label>
                        <input
                            type="number"
                            name="estimated_budget"
                            value="{{ old('estimated_budget') }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('estimated_budget') is-invalid @enderror"
                            placeholder="0.00"
                        >
                        @error('estimated_budget') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Buying Timeline</label>
                        <input
                            type="text"
                            name="buying_timeline"
                            value="{{ old('buying_timeline') }}"
                            class="oy-input @error('buying_timeline') is-invalid @enderror"
                            placeholder="e.g. Within 30 days"
                        >
                        @error('buying_timeline') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">Procurement Process</label>
                        <textarea
                            name="procurement_process"
                            class="oy-textarea @error('procurement_process') is-invalid @enderror"
                            placeholder="How does this organization normally purchase?"
                        >{{ old('procurement_process') }}</textarea>
                        @error('procurement_process') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">Estimated Demand</label>
                        <textarea
                            name="estimated_demand"
                            class="oy-textarea @error('estimated_demand') is-invalid @enderror"
                            placeholder="Expected quantities, frequency or recurring demand..."
                        >{{ old('estimated_demand') }}</textarea>
                        @error('estimated_demand') <div class="oy-error">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Assessment Intelligence</h2>
                <p class="oy-card-description">
                    Record observations, opportunities, risks and the recommended next step.
                </p>
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
                            >{{ old($field) }}</textarea>
                            @error($field) <div class="oy-error">{{ $message }}</div> @enderror
                        </div>
                    @endforeach

                </div>
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                <a href="{{ route('assessments.index') }}" class="oy-btn oy-btn-secondary">
                    Cancel
                </a>

                <button type="submit" class="oy-btn oy-btn-primary">
                    Create Assessment
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
