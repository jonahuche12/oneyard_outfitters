<?php

namespace App\Http\Controllers;

use App\Actions\Stock\ConfirmStockConsignmentDiscrepancy;
use App\Models\StockConsignment;
use App\Models\StockConsignmentDiscrepancy;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockConsignmentDiscrepancyController extends Controller
{
    public function confirm(
        Request $request,
        StockConsignment $consignment,
        StockConsignmentDiscrepancy $discrepancy,
        ConfirmStockConsignmentDiscrepancy $action
    ): RedirectResponse {
        $actor = $request->user();

        $this->authorizeSourceCoordinator($actor, $consignment);

        abort_unless(
            $discrepancy->consignmentItem()
                ->where('stock_consignment_id', $consignment->id)
                ->exists(),
            404
        );

        $action->execute(
            $actor,
            $discrepancy,
            $request->only('resolution_reason')
        );

        return redirect()
            ->route('stock.consignments.show', $consignment)
            ->with('success', 'Consignment discrepancy confirmed.');
    }

    private function authorizeSourceCoordinator(
        User $actor,
        StockConsignment $consignment
    ): void {
        abort_unless($actor->is_active, 403);

        $source = Store::query()->findOrFail($consignment->source_store_id);

        abort_unless($source->is_active, 403);

        $assignment = $actor->activeStoreAssignment;

        abort_unless(
            $assignment
            && $assignment->role === StoreStaff::ROLE_COORDINATOR
            && (int) $assignment->store_id === (int) $source->id,
            403
        );
    }
}
