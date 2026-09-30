<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Actions\Organizations\ExportOrganizationData;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Organization::class);

        $search = trim((string) $request->input('search'));

        $organizations = Organization::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('organization_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('organizations.index', [
            'organizations' => $organizations,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Organization::class);

        return view('organizations.create');
    }

    public function store(
        StoreOrganizationRequest $request
    ): RedirectResponse {
        Gate::authorize('create', Organization::class);

        $validated = $request->validated();

        $organization = DB::transaction(function () use ($validated) {
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

            return $organization;
        });

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization): View
    {
        Gate::authorize('view', $organization);

        $organization->loadCount([
            'contacts',
            'assessments',
            'followUps',
            'productSpecifications',
            'quotations',
        ]);

        $organization->load([
            'productSpecifications' => fn ($query) => $query
                ->withCount('artifacts')
                ->latest('specification_date')
                ->latest('id')
                ->limit(5),

            'quotations' => fn ($query) => $query
                ->latest('quotation_date')
                ->latest('id'),
        ]);

        return view('organizations.show', [
            'organization' => $organization,
        ]);
    }

    public function exportIntelligenceData(
        Organization $organization,
        ExportOrganizationData $exportOrganizationData
    ) {
        Gate::authorize('export', $organization);

        $data = $exportOrganizationData->execute($organization);

        $filename = strtolower(
            $organization->organization_code
            . '-intelligence-export.json'
        );

        return response()->streamDownload(
            function () use ($data): void {
                echo json_encode(
                    $data,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                );
            },
            $filename,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]
        );
    }

    public function edit(Organization $organization): View
    {
        Gate::authorize('update', $organization);

        return view('organizations.edit', [
            'organization' => $organization,
        ]);
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization
    ): RedirectResponse {
        Gate::authorize('update', $organization);

        $organization->update(
            $request->validated()
        );

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    public function activate(
        Organization $organization
    ): RedirectResponse {
        Gate::authorize('activate', $organization);

        $organization->update([
            'is_active' => true,
        ]);

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization activated successfully.');
    }

    public function deactivate(
        Organization $organization
    ): RedirectResponse {
        Gate::authorize('deactivate', $organization);

        $organization->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization deactivated successfully.');
    }
}
