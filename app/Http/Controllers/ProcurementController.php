<?php

namespace App\Http\Controllers;

use App\Http\Requests\Procurement\StoreProcurementRequest;
use App\Http\Requests\Procurement\UpdateProcurementRequest;
use App\Models\Order;
use App\Models\Procurement;
use App\Models\ProcurementOffer;
use App\Models\ProcurementAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Procurement::class);

        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $procurements = Procurement::query()
            ->with([
                'order:id,order_number',
                'createdBy:id,name',
                'attachments:id,procurement_id,product_specification_artifact_id,original_name,mime_type,file_path',
            ])
            ->withCount('offers')
            ->withExists([
                'offers as has_accepted_offer' => function ($query) {
                    $query->where(
                        'status',
                        ProcurementOffer::STATUS_ACCEPTED
                    );
                },
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('item_name', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($query) use ($search) {
                            $query->where(
                                'order_number',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })
            ->when(
                is_string($status) && $status !== '',
                fn ($query) => $query->where('status', $status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('procurements.index', [
            'procurements' => $procurements,
            'search' => $search,
            'status' => $status,
            'statuses' => [
                Procurement::STATUS_DRAFT,
                Procurement::STATUS_READY,
                Procurement::STATUS_IN_PROGRESS,
                Procurement::STATUS_FULFILLED,
                Procurement::STATUS_CANCELLED,
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Procurement::class);

        return view('procurements.create', [
            'order' => null,
        ]);
    }

    public function store(
        StoreProcurementRequest $request
    ): RedirectResponse {
        Gate::authorize('create', Procurement::class);

        $procurement = DB::transaction(function () use ($request) {
            return Procurement::create([
                ...$request->validated(),
                'order_id' => null,
                'created_by' => $request->user()->id,
            ]);
        });

        $this->storePhotos(
            $procurement,
            $request->file('photos', []),
            $request->user()->id
        );

        return redirect()
            ->route('procurements.show', $procurement)
            ->with('success', 'Procurement requirement created successfully.');
    }

    public function createForOrder(Order $order): View
    {
        Gate::authorize(
            'createForOrder',
            [Procurement::class, $order]
        );

        return view('procurements.create', [
            'order' => $order,
        ]);
    }

    public function storeForOrder(
        StoreProcurementRequest $request,
        Order $order
    ): RedirectResponse {
        Gate::authorize(
            'createForOrder',
            [Procurement::class, $order]
        );

        $procurement = DB::transaction(function () use ($request, $order) {
            return Procurement::create([
                ...$request->validated(),
                'order_id' => $order->id,
                'created_by' => $request->user()->id,
            ]);
        });

        $this->storePhotos(
            $procurement,
            $request->file('photos', []),
            $request->user()->id
        );

        return redirect()
            ->route('procurements.show', $procurement)
            ->with('success', 'Order procurement requirement created successfully.');
    }

    public function show(Procurement $procurement): View
    {
        Gate::authorize('view', $procurement);

        $procurement->load([
            'order.organization',
            'createdBy:id,name',
            'updatedBy:id,name',
            'attachments',
            'offers.user:id,name',
        ]);

        return view('procurements.show', [
            'procurement' => $procurement,
        ]);
    }

    public function edit(Procurement $procurement): View
    {
        Gate::authorize('update', $procurement);

        $procurement->load('attachments');

        return view('procurements.edit', [
            'procurement' => $procurement,
        ]);
    }

    public function showAttachment(
        Procurement $procurement,
        ProcurementAttachment $attachment
    ) {
        Gate::authorize('view', $procurement);

        abort_unless(
            $attachment->procurement_id === $procurement->id,
            404
        );

        abort_unless(
            in_array($attachment->mime_type, [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
            ], true),
            404
        );

        abort_unless(
            $attachment->file_path
                && Storage::disk('local')->exists($attachment->file_path),
            404
        );

        return response()->file(
            Storage::disk('local')->path($attachment->file_path),
            [
                'Content-Type' => $attachment->mime_type,
                'Content-Disposition' => 'inline; filename="' .
                    addslashes(
                        $attachment->original_name ?: 'procurement-photo'
                    ) .
                    '"',
            ]
        );
    }

    public function update(
        UpdateProcurementRequest $request,
        Procurement $procurement
    ): RedirectResponse {
        Gate::authorize('update', $procurement);

        $photos = $request->file('photos', []);

        $existingPhotoCount = $procurement->attachments()
            ->whereNull('product_specification_artifact_id')
            ->whereIn('mime_type', [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
            ])
            ->count();

        if ($existingPhotoCount + count($photos) > 3) {
            return back()
                ->withInput()
                ->withErrors([
                    'photos' => 'A procurement requirement can have a maximum of 3 reference photos.',
                ]);
        }

        $procurement->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $this->storePhotos(
            $procurement,
            $photos,
            $request->user()->id
        );

        return redirect()
            ->route('procurements.show', $procurement)
            ->with('success', 'Procurement requirement updated successfully.');
    }
    /**
     * Store procurement reference photos.
     *
     * A procurement can have a maximum of three reference photos.
     */
    private function storePhotos(
        Procurement $procurement,
        array $photos,
        int $uploadedBy
    ): void {
        if ($photos === []) {
            return;
        }

        $storedPaths = [];

        try {
            foreach ($photos as $photo) {
                $extension = strtolower(
                    $photo->getClientOriginalExtension()
                );

                $filename = Str::uuid()->toString()
                    .'.'
                    .$extension;

                $path = $photo->storeAs(
                    'procurements/'.$procurement->id,
                    $filename,
                    'local'
                );

                if ($path === false) {
                    throw new \RuntimeException(
                        'The procurement photo could not be stored.'
                    );
                }

                $storedPaths[] = $path;

                ProcurementAttachment::create([
                    'procurement_id' => $procurement->id,
                    'uploaded_by' => $uploadedBy,
                    'file_path' => $path,
                    'original_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                ]);
            }
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

}
