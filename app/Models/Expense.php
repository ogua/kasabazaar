<?php

namespace App\Models;

use App\Enums\ExpenseScope;
use App\Enums\ExpenseStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'expense_date' => 'date',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_ghs' => 'decimal:2',
        'expense_stage' => ExpenseStage::class,
        'expense_for' => ExpenseScope::class,
    ];

    protected static function booted()
    {
        static::creating(function ($expense) {
            if (empty($expense->reference)) {
                $expense->reference = self::generateReference();
            }
            self::normaliseScope($expense);
            self::syncCurrencyAmounts($expense);
        });

        static::updating(function ($expense) {
            self::normaliseScope($expense);
            self::syncCurrencyAmounts($expense);
        });
    }

    /**
     * Keep the USD and GHS amounts in step. The user may enter either side;
     * whichever they provided is authoritative and the other is derived from
     * the exchange rate.
     */
    protected static function syncCurrencyAmounts(Expense $expense): void
    {
        $rate = (float) $expense->exchange_rate;

        if ($rate <= 0) {
            return;
        }

        if ($expense->exists) {
            $usdChanged = $expense->isDirty('amount_usd');
            $ghsChanged = $expense->isDirty('amount_ghs');

            if ($ghsChanged && ! $usdChanged) {
                $expense->amount_usd = round((float) $expense->amount_ghs / $rate, 2);
            } elseif ($usdChanged || $expense->isDirty('exchange_rate')) {
                $expense->amount_ghs = round((float) $expense->amount_usd * $rate, 2);
            }

            return;
        }

        if (empty($expense->amount_ghs) && ! empty($expense->amount_usd)) {
            $expense->amount_ghs = round((float) $expense->amount_usd * $rate, 2);
        } elseif (empty($expense->amount_usd) && ! empty($expense->amount_ghs)) {
            $expense->amount_usd = round((float) $expense->amount_ghs / $rate, 2);
        }
    }

    /**
     * Keep the scope discriminator and its two subject columns consistent:
     * a container expense never carries a shipment_id, a shipment expense
     * never carries a container_number.
     */
    protected static function normaliseScope(Expense $expense): void
    {
        if (empty($expense->expense_for)) {
            $expense->expense_for = $expense->container_number && ! $expense->shipment_id
                ? ExpenseScope::Container
                : ExpenseScope::Shipment;
        }

        $scope = $expense->expense_for instanceof ExpenseScope
            ? $expense->expense_for
            : ExpenseScope::tryFrom((string) $expense->expense_for);

        if ($scope === ExpenseScope::Container) {
            $expense->shipment_id = null;
        } else {
            $expense->container_number = null;
        }
    }

    public static function generateReference(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->count() + 1;

        return sprintf('EXP-%s-%04d', $date, $count);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(ShipmentContainer::class, 'container_number', 'container_number');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * All expenses attributable to a container: those booked directly against
     * the container plus those booked against a shipment inside it.
     */
    public function scopeForContainer(Builder $query, string|int|null $containerNumber): Builder
    {
        if ($containerNumber === null || $containerNumber === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($containerNumber) {
            $q->where('container_number', $containerNumber)
                ->orWhereHas('shipment', fn (Builder $s) => $s->where('container_number', $containerNumber));
        });
    }

    /**
     * Human-readable reference for whatever the expense is booked against.
     */
    public function getSubjectReferenceAttribute(): ?string
    {
        if ($this->expense_for === ExpenseScope::Container) {
            return $this->container_number ? 'CON'.$this->container_number : null;
        }

        return $this->shipment?->shipping_reference;
    }
}
