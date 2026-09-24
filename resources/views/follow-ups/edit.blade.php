@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Edit Follow-up</h1>

            <p class="oy-page-description">
                {{ $followUp->organization->name }}
                <span class="oy-code">
                    ({{ $followUp->organization->organization_code }})
                </span>
            </p>
        </div>

        <div class="oy-page-actions">
            <a
                href="{{ route('follow-ups.show', $followUp) }}"
                class="oy-btn oy-btn-secondary"
            >
                Cancel
            </a>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('follow-ups.update', $followUp) }}"
        class="oy-form"
    >
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="oy-alert oy-alert-danger mb-6">
                Please correct the highlighted fields and try again.
            </div>
        @endif

        <div class="oy-card">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Follow-up Context</h2>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">Organization</label>

                        <input
                            type="text"
                            value="{{ $followUp->organization->name }} — {{ $followUp->organization->organization_code }}"
                            class="oy-input bg-slate-50"
                            disabled
                        >
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Contact</label>

                        <input
                            type="text"
                            value="{{ $followUp->contact
                                ? $followUp->contact->first_name . ' ' . $followUp->contact->last_name
                                : 'Organization-level follow-up' }}"
                            class="oy-input bg-slate-50"
                            disabled
                        >
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Follow-up Date <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="datetime-local"
                            name="follow_up_date"
                            value="{{ old('follow_up_date', $followUp->follow_up_date->format('Y-m-d\TH:i')) }}"
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
                                    @selected(old('type', $followUp->type) === $value)
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
                            value="{{ old('subject', $followUp->subject) }}"
                            class="oy-input @error('subject') is-invalid @enderror"
                            required
                        >

                        @error('subject')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
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
                            @foreach([
                                'open' => 'Open',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('status', $followUp->status) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('status')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Outcome</h2>
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
                        >{{ old('outcome', $followUp->outcome) }}</textarea>

                        @error('outcome')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Next Action</label>

                        <textarea
                            name="next_action"
                            class="oy-textarea @error('next_action') is-invalid @enderror"
                        >{{ old('next_action', $followUp->next_action) }}</textarea>

                        @error('next_action')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Next Follow-up Date</label>

                        <input
                            type="datetime-local"
                            name="next_follow_up_date"
                            value="{{ old(
                                'next_follow_up_date',
                                $followUp->next_follow_up_date?->format('Y-m-d\TH:i')
                            ) }}"
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
                        >{{ old('notes', $followUp->notes) }}</textarea>

                        @error('notes')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                <a
                    href="{{ route('follow-ups.show', $followUp) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button type="submit" class="oy-btn oy-btn-primary">
                    Save Follow-up
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
