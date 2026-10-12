<div class="oy-form">
    <div class="oy-form-grid oy-form-grid-2">

        <div class="oy-form-group">
            <label for="name" class="oy-form-label">
                Name <span class="oy-form-required">*</span>
            </label>
            <input id="name" name="name" type="text" required
                   value="{{ old('name', $lead->name ?? '') }}"
                   class="oy-input @error('name') is-invalid @enderror"
                   placeholder="Enter lead name">
            @error('name')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group">
            <label for="email" class="oy-form-label">Email</label>
            <input id="email" type="email" name="email"
                   value="{{ old('email', $lead->email ?? '') }}"
                   class="oy-input @error('email') is-invalid @enderror"
                   placeholder="name@example.com">
            @error('email')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group">
            <label for="phone" class="oy-form-label">
                Phone <span class="oy-form-required">*</span>
            </label>
            <input id="phone" name="phone" type="tel" required
                   value="{{ old('phone', $lead->phone ?? '') }}"
                   class="oy-input @error('phone') is-invalid @enderror"
                   placeholder="Enter phone number">
            @error('phone')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group">
            <label for="organization_name" class="oy-form-label">Organization</label>
            <input id="organization_name" name="organization_name" type="text"
                   value="{{ old('organization_name', $lead->organization_name ?? '') }}"
                   class="oy-input @error('organization_name') is-invalid @enderror"
                   placeholder="Organization name">
            @error('organization_name')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group">
            <label for="category" class="oy-form-label">
                Category <span class="oy-form-required">*</span>
            </label>
            <input id="category" name="category" type="text" required
                   value="{{ old('category', $lead->category ?? '') }}"
                   class="oy-input @error('category') is-invalid @enderror"
                   placeholder="Lead category">
            @error('category')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group">
            <label for="source" class="oy-form-label">
                Source <span class="oy-form-required">*</span>
            </label>
            <select id="source" name="source" required
                    class="oy-select @error('source') is-invalid @enderror">
                @foreach([
                    'website' => 'Website',
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'whatsapp' => 'WhatsApp',
                    'referral' => 'Referral',
                    'physical_visit' => 'Physical Visit',
                    'existing_client' => 'Existing Client',
                    'other' => 'Other',
                ] as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('source', $lead->source ?? '') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('source')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group">
            <label for="status" class="oy-form-label">Status</label>
            <select id="status" name="status"
                    class="oy-select @error('status') is-invalid @enderror">
                @foreach(['new', 'contacted', 'qualified', 'converted', 'lost'] as $status)
                    <option value="{{ $status }}"
                        @selected(old('status', $lead->status ?? 'new') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group sm:col-span-2">
            <label for="message" class="oy-form-label">Message</label>
            <textarea id="message" name="message" rows="5"
                      class="oy-textarea @error('message') is-invalid @enderror"
                      placeholder="Lead message or initial enquiry">{{ old('message', $lead->message ?? '') }}</textarea>
            @error('message')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="oy-form-group sm:col-span-2">
            <label for="notes" class="oy-form-label">Internal Notes</label>
            <textarea id="notes" name="notes" rows="4"
                      class="oy-textarea @error('notes') is-invalid @enderror"
                      placeholder="Internal notes for the Oneyard team">{{ old('notes', $lead->notes ?? '') }}</textarea>
            @error('notes')
                <p class="oy-error">{{ $message }}</p>
            @enderror
        </div>

    </div>

    @if($errors->any())
        <div class="oy-alert oy-alert-danger mt-5" role="alert">
            <p class="font-medium">Please correct the highlighted fields.</p>
        </div>
    @endif
</div>
