<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assessment\StoreAssessmentRequest;
use App\Http\Requests\Assessment\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Assessment::class);

        $search = trim((string) $request->input('search'));

        $assessments = Assessment::query()
            ->with('organization', 'assessedBy')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('status', 'like', "%{$search}%")
                        ->orWhere('needs', 'like', "%{$search}%")
                        ->orWhere('current_supplier', 'like', "%{$search}%")
                        ->orWhere('decision_maker', 'like', "%{$search}%")
                        ->orWhereHas('organization', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('organization_code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('assessment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('assessments.index', [
            'assessments' => $assessments,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Assessment::class);

        $organizations = Organization::query()
            ->orderBy('name')
            ->get(['id', 'organization_code', 'name']);

        return view('assessments.create', [
            'organizations' => $organizations,
            'organizationId' => $request->integer('organization_id'),
        ]);
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Assessment::class);

        $validated = $request->validated();

        $assessment = Assessment::create([
            ...$validated,
            'assessed_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('assessments.show', $assessment)
            ->with('success', 'Assessment created successfully.');
    }

    public function show(Assessment $assessment): View
    {
        Gate::authorize('view', $assessment);

        $assessment->load([
            'organization',
            'assessedBy',
        ]);

        return view('assessments.show', [
            'assessment' => $assessment,
        ]);
    }

    public function edit(Assessment $assessment): View
    {
        Gate::authorize('update', $assessment);

        $assessment->load([
            'organization',
            'assessedBy',
        ]);

        return view('assessments.edit', [
            'assessment' => $assessment,
        ]);
    }

    public function update(
        UpdateAssessmentRequest $request,
        Assessment $assessment
    ): RedirectResponse {
        Gate::authorize('update', $assessment);

        $assessment->update(
            $request->validated()
        );

        return redirect()
            ->route('assessments.show', $assessment)
            ->with('success', 'Assessment updated successfully.');
    }
}
