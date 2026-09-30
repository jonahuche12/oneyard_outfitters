<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderNotificationRecipient;
use App\Models\OrderAssignment;
use App\Models\ProductionActivity;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanActivity;
use App\Models\ProductionPlanActivityEvidence;
use App\Models\User;
use App\Mail\OrderApproved;
use App\Mail\OrderApprovedInternal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);

        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $orders = Order::query()
            ->with([
                'organization',
                'quotation',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas(
                            'organization',
                            fn ($organization) => $organization
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere(
                                    'organization_code',
                                    'like',
                                    "%{$search}%"
                                )
                        )
                        ->orWhereHas(
                            'quotation',
                            fn ($quotation) => $quotation->where(
                                'quotation_number',
                                'like',
                                "%{$search}%"
                            )
                        );
                });
            })
            ->when(
                in_array($status, [
                    Order::STATUS_PENDING,
                    Order::STATUS_APPROVED,
                    Order::STATUS_IN_PRODUCTION,
                    Order::STATUS_READY,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_CANCELLED,
                ], true),
                fn ($query) => $query->where('status', $status)
            )
            ->latest('order_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('orders.index', compact('orders', 'search', 'status'));
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load([
            'organization',
            'contact',
            'quotation.organization',
            'quotation.contact',
            'quotation.items',
            'quotation.payments',
            'items.productSpecification',
            'items.quotationItem',
            'notificationRecipients.contact',
            'currentAssignment.user',
            'productionPlan.activities.activity',
        ]);

        $coordinatorCandidates = User::query()
            ->where('is_active', true)
            ->whereHas(
                'roles.permissions',
                fn ($query) => $query->where('slug', 'production.manage')
            )
            ->withCount([
                'assignments as active_orders_count' => fn ($query) =>
                    $query->whereNull('ended_at'),
            ])
            ->orderBy('name')
            ->get();

        $productionActivities = ProductionActivity::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('orders.show', compact(
            'order',
            'coordinatorCandidates',
            'productionActivities'
        ));
    }

    public function edit(Order $order): View
    {
        Gate::authorize('update', $order);

        return view('orders.edit', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'expected_delivery_days' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],
            'expected_delivery_date' => [
                'required',
                'date',
            ],
        ]);

        $order->update([
            'expected_delivery_days' => $validated['expected_delivery_days'],
            'expected_delivery_date' => $validated['expected_delivery_date'],
        ]);

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Order delivery estimate updated successfully.');
    }

    public function resendApproval(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('resendApproval', $order);

        $validated = $request->validate([
            'contact_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'contact_ids.*' => [
                'integer',
                'distinct',
            ],
        ]);

        $contacts = $order->organization
            ->contacts()
            ->where('is_active', true)
            ->whereIn('id', $validated['contact_ids'])
            ->get();

        if ($contacts->count() !== count($validated['contact_ids'])) {
            return back()
                ->withErrors([
                    'contact_ids' => 'One or more selected contacts are invalid or inactive.',
                ])
                ->withInput();
        }

        foreach ($contacts as $contact) {
            $recipient = OrderNotificationRecipient::query()
                ->where('order_id', $order->id)
                ->where('contact_id', $contact->id)
                ->first();

            if (! $recipient) {
                $recipient = OrderNotificationRecipient::create([
                    'order_id' => $order->id,
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'access_token' => Str::random(64),
                ]);
            } else {
                $recipient->update([
                    'email' => $contact->email,
                ]);
            }

            Mail::to($contact->email)
                ->queue(new OrderApproved(
                    $order->load('organization'),
                    $contact,
                    $recipient->access_token
                ));
        }

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'status',
                'Approval email queued successfully for the selected organization contacts.'
            );
    }

    public function assignCoordinator(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('assign', $order);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $coordinator = User::query()
            ->whereKey($validated['user_id'])
            ->where('is_active', true)
            ->whereHas(
                'roles.permissions',
                fn ($query) => $query->where('slug', 'production.manage')
            )
            ->first();

        if (! $coordinator) {
            return back()
                ->withErrors([
                    'user_id' =>
                        'The selected staff member is inactive or is not authorized to coordinate production.',
                ])
                ->withInput();
        }

        $assigned = DB::transaction(function () use ($order, $coordinator, $request): bool {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentAssignment = OrderAssignment::query()
                ->where('order_id', $lockedOrder->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($currentAssignment && $currentAssignment->user_id === $coordinator->id) {
                return false;
            }

            if ($currentAssignment) {
                $currentAssignment->update([
                    'ended_at' => now(),
                ]);
            }

            OrderAssignment::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $coordinator->id,
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
                'reason' => $currentAssignment
                    ? 'Order coordinator reassigned.'
                    : 'Order entered production coordination.',
            ]);

            if ($lockedOrder->status === Order::STATUS_APPROVED) {
                $lockedOrder->update([
                    'status' => Order::STATUS_IN_PRODUCTION,
                ]);
            }

            return true;
        });

        if (! $assigned) {
            return back()
                ->withErrors([
                    'user_id' =>
                        'The selected staff member is already the current order coordinator.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'status',
                'Order coordinator assigned successfully. The order is now in production.'
            );
    }


    public function startProductionActivity(
        Request $request,
        Order $order,
        ProductionPlanActivity $activity
    ): JsonResponse|RedirectResponse {
        Gate::authorize('startActivity', [$order, $activity]);

        $validated = $request->validate([
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $lockedActivity = DB::transaction(function () use (
            $order,
            $activity,
            $validated
        ): ProductionPlanActivity {
            $lockedActivity = ProductionPlanActivity::query()
                ->whereKey($activity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedActivity->status !== ProductionPlanActivity::STATUS_PENDING) {
                abort(
                    422,
                    'This production activity cannot be started from its current status.'
                );
            }

            $lockedActivity->update([
                'status' => ProductionPlanActivity::STATUS_STARTED,
                'started_at' => now(),
                'notes' => $validated['notes'] ?? $lockedActivity->notes,
            ]);

            return $lockedActivity->fresh();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Production activity started successfully.',
                'activity' => [
                    'id' => $lockedActivity->id,
                    'status' => $lockedActivity->status,
                    'started_at' => $lockedActivity->started_at?->toIso8601String(),
                ],
            ]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Production activity started successfully.');
    }

    public function unmarkProductionActivity(
        Request $request,
        Order $order,
        ProductionPlanActivity $activity
    ): JsonResponse|RedirectResponse {
        Gate::authorize('unmarkActivity', [$order, $activity]);

        $lockedActivity = DB::transaction(function () use (
            $activity
        ): ProductionPlanActivity {
            $lockedActivity = ProductionPlanActivity::query()
                ->whereKey($activity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedActivity->status
                !== ProductionPlanActivity::STATUS_COMPLETED
            ) {
                abort(
                    422,
                    'Only a completed production activity can be unmarked.'
                );
            }

            $lockedActivity->update([
                'status' => ProductionPlanActivity::STATUS_STARTED,
                'completed_at' => null,
            ]);

            return $lockedActivity->fresh();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Production activity marked as in progress.',
                'activity' => [
                    'id' => $lockedActivity->id,
                    'status' => $lockedActivity->status,
                    'started_at' => $lockedActivity->started_at?->toIso8601String(),
                    'completed_at' => null,
                ],
            ]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'status',
                'Production activity marked as in progress.'
            );
    }


    public function completeProductionActivity(
        Request $request,
        Order $order,
        ProductionPlanActivity $activity
    ): JsonResponse|RedirectResponse {
        Gate::authorize('completeActivity', [$order, $activity]);

        $validated = $request->validate([
            'evidence' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $evidence = $validated['evidence'];

        $filePath = $evidence->store(
            'production-evidence',
            'local'
        );

        try {
            $lockedActivity = DB::transaction(function () use (
                $activity,
                $validated,
                $evidence,
                $filePath,
                $request
            ): ProductionPlanActivity {
                $lockedActivity = ProductionPlanActivity::query()
                    ->whereKey($activity->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedActivity->status
                    !== ProductionPlanActivity::STATUS_STARTED
                ) {
                    abort(
                        422,
                        'This production activity cannot be completed from its current status.'
                    );
                }

                ProductionPlanActivityEvidence::create([
                    'production_plan_activity_id' => $lockedActivity->id,
                    'uploaded_by' => $request->user()->id,
                    'file_path' => $filePath,
                    'original_name' => $evidence->getClientOriginalName(),
                    'mime_type' => $evidence->getMimeType(),
                    'file_size' => $evidence->getSize(),
                    'note' => $validated['notes'] ?? null,
                ]);

                $lockedActivity->update([
                    'status' => ProductionPlanActivity::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'notes' => $validated['notes']
                        ?? $lockedActivity->notes,
                ]);

                return $lockedActivity->fresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($filePath);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Production activity completed successfully.',
                'activity' => [
                    'id' => $lockedActivity->id,
                    'status' => $lockedActivity->status,
                    'completed_at' => $lockedActivity->completed_at?->toIso8601String(),
                ],
                'completed_at' => $lockedActivity->completed_at?->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'status',
                'Production activity completed successfully.'
            );
    }


    public function createProductionPlan(
        Request $request,
        Order $order
    ): RedirectResponse {
        Gate::authorize('manageProductionPlan', $order);

        $validated = $request->validate([
            'activity_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'activity_ids.*' => [
                'integer',
                'distinct',
                'exists:production_activities,id',
            ],
        ]);

        $activities = ProductionActivity::query()
            ->where('is_active', true)
            ->whereIn('id', $validated['activity_ids'])
            ->orderBy('sort_order')
            ->get();

        if ($activities->count() !== count($validated['activity_ids'])) {
            return back()
                ->withErrors([
                    'activity_ids' =>
                        'One or more selected production activities are invalid or inactive.',
                ])
                ->withInput();
        }

        $requiredActivityIds = ProductionActivity::query()
            ->where('is_required', true)
            ->where('is_active', true)
            ->pluck('id');

        if ($requiredActivityIds->diff($activities->pluck('id'))->isNotEmpty()) {
            return back()
                ->withErrors([
                    'activity_ids' =>
                        'Quality Control and Delivery are required production activities.',
                ])
                ->withInput();
        }

        if ($order->productionPlan()->exists()) {
            return back()
                ->withErrors([
                    'activity_ids' =>
                        'A production plan already exists for this order.',
                ]);
        }

        DB::transaction(function () use ($order, $activities, $request): void {
            $plan = ProductionPlan::create([
                'order_id' => $order->id,
                'coordinator_id' => $request->user()->id,
                'created_by' => $request->user()->id,
            ]);

            foreach ($activities as $index => $activity) {
                ProductionPlanActivity::create([
                    'production_plan_id' => $plan->id,
                    'production_activity_id' => $activity->id,
                    'sort_order' => $index + 1,
                    'status' => ProductionPlanActivity::STATUS_PENDING,
                ]);
            }
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Production plan created successfully.');
    }

    public function approve(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('approve', $order);

        $validated = $request->validate([
            'contact_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'contact_ids.*' => [
                'integer',
                'distinct',
            ],
        ]);

        $contacts = $order->organization
            ->contacts()
            ->where('is_active', true)
            ->whereIn('id', $validated['contact_ids'])
            ->get();

        if ($contacts->count() !== count($validated['contact_ids'])) {
            return back()
                ->withErrors([
                    'contact_ids' => 'One or more selected contacts are invalid or inactive.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($order, $contacts): void {
            $order->update([
                'status' => Order::STATUS_APPROVED,
            ]);

            foreach ($contacts as $contact) {
                $recipient = OrderNotificationRecipient::create([
                    'order_id' => $order->id,
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'access_token' => Str::random(64),
                    'notified_at' => now(),
                ]);

                Mail::to($recipient->email)
                    ->queue(new OrderApproved(
                        $order->load('organization'),
                        $contact,
                        $recipient->access_token
                    ));
            }

            $superAdmins = \App\Models\User::query()
                ->where('is_active', true)
                ->whereHas(
                    'roles',
                    fn ($query) => $query->where('slug', 'super-admin')
                )
                ->get();

            foreach ($superAdmins as $superAdmin) {
                Mail::to($superAdmin->email)
                    ->queue(new OrderApprovedInternal(
                        $order->load('organization')
                    ));
            }
        });

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'status',
                'Order approved successfully and selected organization contacts have been notified.'
            );
    }
}
