<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Lead::class);

        $search = trim((string) $request->input('search'));

        $leads = Lead::query()
            ->with(['createdBy', 'assignedTo', 'organization'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('organization_name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('leads.index', compact('leads', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', Lead::class);

        return view('leads.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Lead::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'source' => [
                'required',
                'string',
                'in:website,facebook,instagram,whatsapp,referral,physical_visit,existing_client,other',
            ],
            'status' => [
                'nullable',
                'string',
                'in:new,contacted,qualified,converted,lost',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead = Lead::create([
            ...$validated,
            'status' => $validated['status'] ?? Lead::STATUS_NEW,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead created successfully.');
    }

    public function show(Lead $lead): View
    {
        Gate::authorize('view', $lead);

        $lead->load(['createdBy', 'assignedTo', 'organization']);

        return view('leads.show', compact('lead'));
    }

    public function edit(Lead $lead): View
    {
        Gate::authorize('update', $lead);

        return view('leads.edit', compact('lead'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('update', $lead);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'source' => [
                'required',
                'string',
                'in:website,facebook,instagram,whatsapp,referral,physical_visit,existing_client,other',
            ],
            'status' => [
                'required',
                'string',
                'in:new,contacted,qualified,converted,lost',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead->update($validated);

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        Gate::authorize('delete', $lead);

        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead deleted successfully.');
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('assign', $lead);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $lead->update([
            'assigned_to' => $validated['assigned_to'] ?? null,
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead assignment updated successfully.');
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('convert', $lead);

        if ($lead->organization_id !== null) {
            return redirect()
                ->route('leads.show', $lead)
                ->with('error', 'This lead has already been converted.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'type' => [
                'required',
                'string',
                Rule::in([
                    'school',
                    'company',
                    'church',
                    'ngo',
                    'government',
                    'association',
                    'other',
                ]),
            ],
            'ownership' => [
                'required',
                'string',
                Rule::in([
                    'private',
                    'public',
                    'government',
                    'non_profit',
                    'other',
                ]),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'lga' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $organization = DB::transaction(function () use ($validated, $lead) {
            $organization = Organization::create([
                ...$validated,
                'organization_code' => 'ORG-TMP-' . Str::random(16),
                'country' => $validated['country'] ?? 'Nigeria',
                'is_active' => true,
            ]);

            $organization->update([
                'organization_code' => sprintf(
                    'ORG-%06d',
                    $organization->id
                ),
            ]);

            $lead->update([
                'organization_id' => $organization->id,
                'status' => Lead::STATUS_CONVERTED,
                'converted_at' => now(),
            ]);

            return $organization;
        });

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Lead converted to organization successfully.');
    }
}
