<?php

namespace App\Models;


use App\Actions\Earnings\CreateOrderEarningsPool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IN_PRODUCTION = 'in_production';
    public const STATUS_READY_FOR_QUALITY_CONTROL = 'ready_for_quality_control';
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'organization_id',
        'quotation_id',
        'contact_id',
        'order_date',
        'expected_delivery_days',
        'expected_delivery_date',
        'status',
        'subtotal',
        'discount',
        'additional_charges',
        'total',
        'terms',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_days' => 'integer',
            'expected_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'additional_charges' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Human-readable labels for every order status.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_IN_PRODUCTION => 'In Production',
            self::STATUS_READY_FOR_QUALITY_CONTROL => 'Ready for QC',
            self::STATUS_CORRECTION_REQUIRED => 'Correction Required',
            self::STATUS_READY => 'Ready',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)
            ->orderBy('sort_order');
    }

    public function notificationRecipients(): HasMany
    {
        return $this->hasMany(OrderNotificationRecipient::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderAssignment::class)
            ->latest('assigned_at');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(OrderAssignment::class)
            ->whereNull('ended_at')
            ->latestOfMany('id');
    }

    public function productionPlan(): HasOne
    {
        return $this->hasOne(ProductionPlan::class);
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(Procurement::class);
    }

    public function qualityControlInspections(): HasMany
    {
        return $this->hasMany(QualityControlInspection::class)
            ->latest('id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function scopeVisibleTo(
        \Illuminate\Database\Eloquent\Builder $query,
        \App\Models\User $user
    ): \Illuminate\Database\Eloquent\Builder {
        if ($user->isAdministrator()) {
            return $query;
        }

        return $query->whereHas(
            'currentAssignment',
            fn ($assignment) => $assignment->where('user_id', $user->id)
        );
    }

    protected static function booted(): void
    {

        static::updated(static function (Order $order): void {
            if (
                $order->wasChanged('status')
                && $order->status === self::STATUS_DELIVERED
            ) {
                $pool = app(CreateOrderEarningsPool::class)->execute($order);

                // Contribution derivation must never block delivery.
                try {
                    app(\App\Actions\Earnings\DeriveOrderEarningsContributions::class)
                        ->execute($pool);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }
}
