@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Record Follow-up</h1>

            <p class="oy-page-description">
                Record what happened, what was learned and what should happen next.
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('follow-ups.index') }}" class="oy-btn oy-btn-secondary">
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('follow-ups.store') }}" class="oy-form">
        @csrf

        @if($errors->any())
            <div class="oy-alert oy-alert-danger mb-6">
                Please correct the highlighted fields and try again.
            </div>
        @endif

        <div class="oy-card">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Follow-up Context</h2>

                <p class="oy-card-description">
                    Identify the organization and, where applicable, the contact involved.
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
                        <label class="oy-form-label">Contact</label>

                        <select
                            name="contact_id"
                            class="oy-select @error('contact_id') is-invalid @enderror"
                            {{ $organizationId ? '' : 'disabled' }}
                        >
                            <option value="">Organization-level follow-up</option>

                            @foreach($contacts as $contact)
                                <option
                                    value="{{ $contact->id }}"
                                    @selected(old('contact_id', $contactId) == $contact->id)
                                >
                                    {{ $contact->first_name }}
                                    {{ $contact->middle_name }}
                                    {{ $contact->last_name }}
                                    @if($contact->position)
                                        — {{ $contact->position }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @error('contact_id')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Follow-up Date <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="datetime-local"
                            name="follow_up_date"
                            value="{{ old('follow_up_date', now()->format('Y-m-d\TH:i')) }}"
                            class="oy-input @error('follow_up_date') is-invalid @enderror"
                            required
                        >

                        @error('follow_up_date')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Type <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="type"
                            class="oy-select @error('type') is-invalid @enderror"
                            required
                        >
                            @foreach([
                                'call' => 'Call',
                                'visit' => 'Visit',
                                'meeting' => 'Meeting',
                                'whatsapp' => 'WhatsApp',
                                'email' => 'Email',
                                'message' => 'Message',
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
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">
                            Subject <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="text"
                            name="subject"
                            value="{{ old('subject') }}"
                            class="oy-input @error('subject') is-invalid @enderror"
                            placeholder="What was this follow-up about?"
                            required
                        >

                        @error('subject')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Outcome</h2>

                <p class="oy-card-description">
                    Record the actual result of the interaction.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid">

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Outcome <span class="oy-form-required">*</span>
                        </label>

                        <textarea
                            name="outcome"
                            class="oy-textarea @error('outcome') is-invalid @enderror"
                            required
                            placeholder="What happened during the interaction?"
                        >{{ old('outcome') }}</textarea>

                        @error('outcome')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Next Action</label>

                        <textarea
                            name="next_action"
                            class="oy-textarea @error('next_action') is-invalid @enderror"
                            placeholder="What should Oneyard do next?"
                        >{{ old('next_action') }}</textarea>

                        @error('next_action')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Next Follow-up Date</label>

                        <input
                            type="datetime-local"
                            name="next_follow_up_date"
                            value="{{ old('next_follow_up_date') }}"
                            class="oy-input @error('next_follow_up_date') is-invalid @enderror"
                        >

                        @error('next_follow_up_date')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Internal Notes</label>

                        <textarea
                            name="notes"
                            class="oy-textarea @error('notes') is-invalid @enderror"
                            placeholder="Additional internal information..."
                        >{{ old('notes') }}</textarea>

                        @error('notes')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                <a
                    href="{{ route('follow-ups.index') }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button type="submit" class="oy-btn oy-btn-primary">
                    Record Follow-up
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
