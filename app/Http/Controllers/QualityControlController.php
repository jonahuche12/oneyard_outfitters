<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\QualityControlInspection;
use App\Models\QualityControlInspectionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QualityControlController extends Controller
{
    public function index(): View
    {
        abort_unless(
            auth()->user()->can('viewQualityControl', Order::class),
            403
        );

        $orders = Order::query()
            ->with([
                'organization',
                'items',
                'currentAssignment.user',
            ])
            ->where('status', Order::STATUS_READY_FOR_QUALITY_CONTROL)
            ->latest('id')
            ->paginate(20);

        return view('quality-control.index', compact('orders'));
    }

    public function start(Order $order): RedirectResponse
    {
        Gate::authorize('inspectQualityControl', $order);

        $existing = $order->qualityControlInspections()
            ->where('status', QualityControlInspection::STATUS_IN_PROGRESS)
            ->first();

        if ($existing) {
            return redirect()
                ->route('quality-control.show', $existing)
                ->with('info', 'This order already has an active Quality Control inspection.');
        }

        $inspection = DB::transaction(function () use ($order): QualityControlInspection {
            $inspection = QualityControlInspection::create([
                'order_id' => $order->id,
                'inspected_by' => auth()->id(),
                'status' => QualityControlInspection::STATUS_IN_PROGRESS,
            ]);

            foreach ($order->items as $item) {
                QualityControlInspectionItem::create([
                    'quality_control_inspection_id' => $inspection->id,
                    'order_item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'failed_quantity' => strtolower((string) $item->unit) === 'piece'
                        ? 0
                        : null,
                ]);
            }

            return $inspection;
        });

        return redirect()
            ->route('quality-control.show', $inspection)
            ->with('success', 'Quality Control inspection started.');
    }

    public function show(QualityControlInspection $inspection): View
    {
        $order = $inspection->order;

        Gate::authorize('viewQualityControl', $order);

        $inspection->load([
            'order.organization',
            'order.items',
            'items.orderItem',
            'inspectedBy',
            'approvedBy',
        ]);

        return view('quality-control.show', compact('inspection', 'order'));
    }

    public function complete(
        Request $request,
        QualityControlInspection $inspection
    ): RedirectResponse {
        $order = $inspection->order;

        Gate::authorize('approveQualityControl', $order);

        if (!$inspection->isInProgress()) {
            return redirect()
                ->route('quality-control.show', $inspection)
                ->with('error', 'This Quality Control inspection has already been completed.');
        }

        $inspection->load('items');

        $validated = $request->validate([
            'findings' => ['nullable', 'string', 'max:10000'],
            'correction_notes' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array'],
            'items.*.failed_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.findings' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use (
            $inspection,
            $order,
            $validated
        ): void {
            $hasFailure = false;

            foreach ($inspection->items as $inspectionItem) {
                $payload = $validated['items'][$inspectionItem->id] ?? [];

                $failedQuantity = null;

                if ($inspectionItem->isPiece()) {
                    $failedQuantity = (float) ($payload['failed_quantity'] ?? 0);

                    if ($failedQuantity > (float) $inspectionItem->quantity) {
                        abort(
                            422,
                            "Failed quantity for {$inspectionItem->item_name} cannot exceed the ordered quantity."
                        );
                    }

                    if ($failedQuantity > 0) {
                        $hasFailure = true;
                    }
                }

                $inspectionItem->update([
                    'failed_quantity' => $failedQuantity,
                    'findings' => $payload['findings'] ?? null,
                ]);
            }

            $inspection->update([
                'status' => QualityControlInspection::STATUS_COMPLETED,
                'result' => $hasFailure
                    ? QualityControlInspection::RESULT_FAIL
                    : QualityControlInspection::RESULT_PASS,
                'findings' => $validated['findings'] ?? null,
                'correction_required' => $hasFailure,
                'correction_notes' => $hasFailure
                    ? ($validated['correction_notes'] ?? null)
                    : null,
                'inspected_at' => now(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $order->update([
                'status' => $hasFailure
                    ? Order::STATUS_CORRECTION_REQUIRED
                    : Order::STATUS_READY,
            ]);
        });

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'success',
                $inspection->fresh()->isFailed()
                    ? 'Quality Control failed. Correction is required before the order can return to Quality Control.'
                    : 'Quality Control passed. The order is now ready for delivery.'
            );
    }
}
