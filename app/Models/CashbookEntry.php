<?php

namespace App\Models;

use App\Enums\CashbookCostCenter;
use App\Enums\ExpenditureLedgerType;
use App\Enums\IncomeLedgerType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\CashbookIncomeLedger;
use App\Models\CashbookExpenditureLedger;

class CashbookEntry extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'date'          => 'date',
        'bank_debit'    => 'decimal:2',
        'momo_debit'    => 'decimal:2',
        'bank_credit'   => 'decimal:2',
        'momo_credit'   => 'decimal:2',
        'bank_balance'  => 'decimal:2',
        'momo_balance'  => 'decimal:2',
        'cost_center'   => CashbookCostCenter::class,
        // receipt analysis
        'op_balance'            => 'decimal:2',
        'sales'                 => 'decimal:2',
        'dir_transfer'          => 'decimal:2',
        'shipping_fee'          => 'decimal:2',
        'service_fee'           => 'decimal:2',
        'momo_interest'         => 'decimal:2',
        'property_management'   => 'decimal:2',
        'refund'                => 'decimal:2',
        'contra_receipt'        => 'decimal:2',
        // expenditure analysis
        'import_duty'           => 'decimal:2',
        'shipping_expenses'     => 'decimal:2',
        'salaries_wages'        => 'decimal:2',
        'bank_charges'          => 'decimal:2',
        'ssnit'                 => 'decimal:2',
        'paye'                  => 'decimal:2',
        'withholding_tax'       => 'decimal:2',
        'momo_charges'          => 'decimal:2',
        'contra_payment'        => 'decimal:2',
        'transportation'        => 'decimal:2',
        'materials'             => 'decimal:2',
        'donation'              => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            $entry->computeRunningBalance();
            $entry->fillAnalysisColumn();
        });

        static::updating(function (self $entry) {
            $entry->computeRunningBalance();
            $entry->fillAnalysisColumn();
        });

        static::saved(function (self $entry) {
            static::rebalanceAfter($entry);
            static::updateLedgers($entry->month, $entry->year);
        });

        static::deleted(function (self $entry) {
            static::rebalanceAfter($entry);
            static::updateLedgers($entry->month, $entry->year);
        });
    }

    /**
     * Compute bank_balance and momo_balance from the previous entry.
     */
    protected function computeRunningBalance(): void
    {
        $prev = static::withoutTrashed()
            ->where(function ($q) {
                $q->where('date', '<', $this->date)
                    ->orWhere(function ($q2) {
                        $q2->where('date', '=', $this->date)
                            ->where('id', '<', $this->id ?? '');
                    });
            })
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->first();

        if ($prev) {
            $this->bank_balance = round((float) $prev->bank_balance + (float) $this->bank_debit - (float) $this->bank_credit, 2);
            $this->momo_balance = round((float) $prev->momo_balance + (float) $this->momo_debit - (float) $this->momo_credit, 2);
        } else {
            $this->bank_balance = round((float) $this->bank_debit - (float) $this->bank_credit, 2);
            $this->momo_balance = round((float) $this->momo_debit - (float) $this->momo_credit, 2);
        }
    }

    /**
     * Zero all analysis columns then set the one matching cost_center.
     */
    protected function fillAnalysisColumn(): void
    {
        foreach (CashbookCostCenter::allAnalysisColumns() as $col) {
            $this->{$col} = null;
        }

        $col = $this->cost_center->analysisColumn();

        if ($this->cost_center->isReceipt()) {
            $this->{$col} = (float) $this->bank_debit + (float) $this->momo_debit ?: null;
        } else {
            $this->{$col} = (float) $this->bank_credit + (float) $this->momo_credit ?: null;
        }
    }

    /**
     * Recompute balance for all entries that come after the given entry.
     */
    protected static function rebalanceAfter(self $pivot): void
    {
        DB::transaction(function () use ($pivot) {
            $subsequent = static::withoutTrashed()
                ->where(function ($q) use ($pivot) {
                    $q->where('date', '>', $pivot->date)
                        ->orWhere(function ($q2) use ($pivot) {
                            $q2->where('date', '=', $pivot->date)
                                ->where('id', '>', $pivot->id);
                        });
                })
                ->orderBy('date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $prev = static::withoutTrashed()
                ->where(function ($q) use ($pivot) {
                    $q->where('date', '<', $pivot->date)
                        ->orWhere(function ($q2) use ($pivot) {
                            $q2->where('date', '=', $pivot->date)
                                ->where('id', '<=', $pivot->id);
                        });
                })
                ->orderByDesc('date')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $bankBal = $prev ? (float) $prev->bank_balance : 0;
            $momoBal = $prev ? (float) $prev->momo_balance : 0;

            foreach ($subsequent as $entry) {
                $bankBal = round($bankBal + (float) $entry->bank_debit - (float) $entry->bank_credit, 2);
                $momoBal = round($momoBal + (float) $entry->momo_debit - (float) $entry->momo_credit, 2);
                static::withoutEvents(function () use ($entry, $bankBal, $momoBal) {
                    $entry->updateQuietly([
                        'bank_balance' => $bankBal,
                        'momo_balance' => $momoBal,
                    ]);
                });
            }
        });
    }

    /**
     * Rebuild cumulative income and expenditure ledger rows for a month/year.
     */
    protected static function updateLedgers(int $month, int $year): void
    {
        // Income ledgers
        foreach (IncomeLedgerType::cases() as $ledgerType) {
            $costCenters = $ledgerType->costCenters();
            $credit = static::withoutTrashed()
                ->where('month', $month)
                ->where('year', $year)
                ->whereIn('cost_center', $costCenters)
                ->sum('bank_debit') + static::withoutTrashed()
                ->where('month', $month)
                ->where('year', $year)
                ->whereIn('cost_center', $costCenters)
                ->sum('momo_debit');

            $ledger = CashbookIncomeLedger::firstOrNew([
                'month'       => $month,
                'year'        => $year,
                'ledger_type' => $ledgerType->value,
            ]);
            $ledger->credit = round((float) $credit, 2);
            $ledger->cl_balance = round((float) $ledger->op_balance + (float) $ledger->credit - (float) $ledger->debit, 2);
            $ledger->save();
        }

        // Expenditure ledgers
        foreach (ExpenditureLedgerType::cases() as $ledgerType) {
            $costCenter = $ledgerType->costCenter();
            if ($costCenter === null) {
                continue;
            }
            $debit = static::withoutTrashed()
                ->where('month', $month)
                ->where('year', $year)
                ->where('cost_center', $costCenter)
                ->sum('bank_credit') + static::withoutTrashed()
                ->where('month', $month)
                ->where('year', $year)
                ->where('cost_center', $costCenter)
                ->sum('momo_credit');

            $ledger = CashbookExpenditureLedger::firstOrNew([
                'month'       => $month,
                'year'        => $year,
                'ledger_type' => $ledgerType->value,
            ]);
            $ledger->debit = round((float) $debit, 2);
            $ledger->cl_balance = round((float) $ledger->op_balance + (float) $ledger->debit - (float) $ledger->credit, 2);
            $ledger->save();
        }
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public static function monthlyTotals(int $month, int $year): array
    {
        return static::withoutTrashed()
            ->where('month', $month)
            ->where('year', $year)
            ->selectRaw('
                SUM(bank_debit)        AS bank_debit,
                SUM(momo_debit)        AS momo_debit,
                SUM(bank_credit)       AS bank_credit,
                SUM(momo_credit)       AS momo_credit,
                SUM(op_balance)        AS op_balance,
                SUM(sales)             AS sales,
                SUM(dir_transfer)      AS dir_transfer,
                SUM(shipping_fee)      AS shipping_fee,
                SUM(service_fee)       AS service_fee,
                SUM(momo_interest)     AS momo_interest,
                SUM(property_management) AS property_management,
                SUM(refund)            AS refund,
                SUM(contra_receipt)    AS contra_receipt,
                SUM(import_duty)       AS import_duty,
                SUM(shipping_expenses) AS shipping_expenses,
                SUM(salaries_wages)    AS salaries_wages,
                SUM(bank_charges)      AS bank_charges,
                SUM(ssnit)             AS ssnit,
                SUM(paye)              AS paye,
                SUM(withholding_tax)   AS withholding_tax,
                SUM(momo_charges)      AS momo_charges,
                SUM(contra_payment)    AS contra_payment,
                SUM(transportation)    AS transportation,
                SUM(materials)         AS materials,
                SUM(donation)          AS donation
            ')
            ->first()
            ?->toArray() ?? [];
    }
}
