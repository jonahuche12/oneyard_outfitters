<?php

namespace App\Http\Controllers;

use App\Actions\ProductSpecifications\CreateProductSpecification;
use App\Actions\ProductSpecifications\UpdateProductSpecification;
use App\Http\Requests\StoreProductSpecificationRequest;
use App\Http\Requests\UpdateProductSpecificationRequest;
use App\Models\Organization;
use App\Models\ProductSpecification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductSpecificationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ProductSpecification::class);

        $search = trim((string) $request->input('search'));

        $specifications = ProductSpecification::query()
            ->with(['organization', 'contact', 'createdBy'])
            ->withCount('artifacts')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('item_name', 'like', "%{$search}%")
                        ->orWhere('product_type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('material', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('organization', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('organization_code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('specification_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('product-specifications.index', [
            'specifications' => $specifications,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', ProductSpecification::class);

        $organizations = Organization::query()
            ->orderBy('name')
            ->get([
                'id',
                'organization_code',
                'name',
            ]);

        $organizationId = $request->integer('organization_id');

        $contacts = collect();

        if ($organizationId > 0) {
            $contacts = \App\Models\Contact::query()
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return view('product-specifications.create', [
            'organizations' => $organizations,
            'organizationId' => $organizationId,
            'contacts' => $contacts,
        ]);
    }

    public function store(
        StoreProductSpecificationRequest $request,
        CreateProductSpecification $createProductSpecification
    ): RedirectResponse {
        Gate::authorize('create', ProductSpecification::class);

        $specification = $createProductSpecification->execute(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('product-specifications.show', $specification)
            ->with('success', 'Product specification created successfully.');
    }

    public function show(ProductSpecification $productSpecification): View
    {
        Gate::authorize('view', $productSpecification);

        $productSpecification->load([
            'organization',
            'contact',
            'createdBy',
            'artifacts' => fn ($query) => $query
                ->with('uploadedBy')
                ->latest('created_at'),
        ]);

        return view('product-specifications.show', [
            'productSpecification' => $productSpecification,
        ]);
    }

    public function edit(ProductSpecification $productSpecification): View
    {
        Gate::authorize('update', $productSpecification);

        $productSpecification->load([
            'organization',
            'contact',
            'createdBy',
        ]);

        $contacts = \App\Models\Contact::query()
            ->where('organization_id', $productSpecification->organization_id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('product-specifications.edit', [
            'productSpecification' => $productSpecification,
            'contacts' => $contacts,
        ]);
    }

    public function update(
        UpdateProductSpecificationRequest $request,
        ProductSpecification $productSpecification,
        UpdateProductSpecification $updateProductSpecification
    ): RedirectResponse {
        Gate::authorize('update', $productSpecification);

        $updateProductSpecification->execute(
            $productSpecification,
            $request->validated()
        );

        return redirect()
            ->route('product-specifications.show', $productSpecification)
            ->with('success', 'Product specification updated successfully.');
    }
}
