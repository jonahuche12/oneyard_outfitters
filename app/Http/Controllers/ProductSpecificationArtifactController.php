<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductSpecificationArtifactRequest;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationArtifact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductSpecificationArtifactController extends Controller
{
    public function store(
        StoreProductSpecificationArtifactRequest $request,
        ProductSpecification $productSpecification
    ): RedirectResponse {
        Gate::authorize('update', $productSpecification);

        $file = $request->file('file');

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $filename = Str::uuid()->toString()
            .'.'
            .$extension;

        $directory = 'product-specifications/'
            .$productSpecification->id;

        $path = $file->storeAs(
            $directory,
            $filename,
            'local'
        );

        if ($path === false) {
            abort(500, 'The artifact file could not be stored.');
        }

        try {
            DB::transaction(function () use (
                $request,
                $productSpecification,
                $file,
                $path
            ): void {
                ProductSpecificationArtifact::query()
                    ->where(
                        'product_specification_id',
                        $productSpecification->id
                    )
                    ->where(
                        'artifact_type',
                        $request->validated('artifact_type')
                    )
                    ->where('is_current', true)
                    ->update([
                        'is_current' => false,
                    ]);

                ProductSpecificationArtifact::create([
                    'product_specification_id' => $productSpecification->id,
                    'artifact_type' => $request->validated('artifact_type'),
                    'title' => $request->validated('title'),
                    'description' => $request->validated('description'),
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                    'is_current' => true,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return redirect()
            ->route(
                'product-specifications.show',
                $productSpecification
            )
            ->with(
                'success',
                'Product specification artifact uploaded successfully.'
            );
    }

    public function preview(
        ProductSpecificationArtifact $productSpecificationArtifact
    ) {
        $productSpecificationArtifact->loadMissing(
            'productSpecification'
        );

        Gate::authorize(
            'view',
            $productSpecificationArtifact->productSpecification
        );

        abort_unless(
            in_array(
                strtolower((string) $productSpecificationArtifact->mime_type),
                ['image/jpeg', 'image/png', 'image/webp'],
                true
            ),
            404
        );

        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($productSpecificationArtifact->file_path),
            404
        );

        return response()->file(
            $disk->path($productSpecificationArtifact->file_path),
            [
                'Content-Type' => $productSpecificationArtifact->mime_type,
                'Content-Disposition' => 'inline; filename="' .
                    addslashes($productSpecificationArtifact->original_filename) .
                    '"',
            ]
        );
    }

    public function download(
        ProductSpecificationArtifact $productSpecificationArtifact
    ): StreamedResponse {
        $productSpecificationArtifact->loadMissing(
            'productSpecification'
        );

        Gate::authorize(
            'view',
            $productSpecificationArtifact->productSpecification
        );

        abort_unless(
            Storage::disk('local')->exists(
                $productSpecificationArtifact->file_path
            ),
            404
        );

        return Storage::disk('local')->download(
            $productSpecificationArtifact->file_path,
            $productSpecificationArtifact->original_filename
        );
    }

    public function destroy(
        ProductSpecificationArtifact $productSpecificationArtifact
    ): RedirectResponse {
        $productSpecificationArtifact->loadMissing(
            'productSpecification'
        );

        Gate::authorize(
            'update',
            $productSpecificationArtifact->productSpecification
        );

        $productSpecificationArtifact->delete();

        return redirect()
            ->route(
                'product-specifications.show',
                $productSpecificationArtifact->productSpecification
            )
            ->with(
                'success',
                'Product specification artifact removed successfully.'
            );
    }
}
