<?php

namespace App\Service;

use App\Models\Shipment;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Client;
use App\Models\PayrollEntry;
use App\Enums\IncomeStatus;
use Carbon\Carbon;

class ReportService
{
    protected ExpenseService $expenseService;
    protected IncomeService $incomeService;
    protected PayrollService $payrollService;

    public function __construct(
        ExpenseService $expenseService,
        IncomeService $incomeService,
        PayrollService $payrollService
    ) {
        $this->expenseService = $expenseService;
        $this->incomeService = $incomeService;
        $this->payrollService = $payrollService;
    }

    /**
     * Generate shipments by container report
     */
    public function shipmentsByContainer(string $containerNumber): array
    {
        $shipments = Shipment::where('shipping_reference', 'like', "{$containerNumber}-%")
            ->with(['client', 'receivers.items', 'expenses', 'payments'])
            ->get();

        return [
            'container' => $containerNumber,
            'total_shipments' => $shipments->count(),
            'total_revenue' => $shipments->sum('total'),
            'total_paid' => $shipments->sum('paid'),
            'total_expenses' => $shipments->sum(fn ($s) => $s->expenses->sum('amount_usd')),
            'net_profit' => $shipments->sum('total') - $shipments->sum(fn ($s) => $s->expenses->sum('amount_usd')),
            'shipments' => $shipments->map(fn ($s) => [
                'reference' => $s->shipping_reference,
                'client' => [
                    'name' => $s->client?->name,
                    'phone' => $s->client?->phone,
                ],
                'receivers' => $s->receivers->map(fn ($r) => [
                    'name' => $r->receiver_name ?: 'SELF',
                    'contact' => $r->receiver_phone,
                    'location' => $r->city,
                    'items' => $r->items,
                ]),
                'status' => $s->status,
                'total' => $s->total,
                'paid' => $s->paid,
                'expenses' => $s->expenses->sum('amount_usd'),
            ]),
        ];
    }

    /**
     * Generate shipments by year report
     */
    public function shipmentsByYear(int $year): array
    {
        $yearSuffix = substr((string) $year, -2);

        $shipments = Shipment::where('shipping_reference', 'like', "%-{$yearSuffix}-%")
            ->with(['client', 'expenses'])
            ->get();

        $monthlyBreakdown = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthShipments = $shipments->filter(fn ($s) => $s->created_at?->month === $month);
            $monthlyBreakdown[$month] = [
                'month' => Carbon::create($year, $month)->format('F'),
                'count' => $monthShipments->count(),
                'revenue' => $monthShipments->sum('total'),
                'expenses' => $monthShipments->sum(fn ($s) => $s->expenses->sum('amount_usd')),
            ];
        }

        return [
            'year' => $year,
            'total_shipments' => $shipments->count(),
            'monthly_breakdown' => $monthlyBreakdown,
            'total_revenue' => $shipments->sum('total'),
            'total_expenses' => $shipments->sum(fn ($s) => $s->expenses->sum('amount_usd')),
            'net_profit' => $shipments->sum('total') - $shipments->sum(fn ($s) => $s->expenses->sum('amount_usd')),
        ];
    }

    /**
     * Generate shipments by container sequence report
     */
    public function shipmentsByContainerSequence(int $year, int $sequence): array
    {
        $yearSuffix = substr((string) $year, -2);
        $seqCode = "C{$sequence}";

        $shipments = Shipment::where('shipping_reference', 'like', "%-{$yearSuffix}-{$seqCode}-%")
            ->with(['client', 'receivers', 'expenses'])
            ->get();

        return [
            'year' => $year,
            'container_sequence' => $seqCode,
            'total_shipments' => $shipments->count(),
            'total_revenue' => $shipments->sum('total'),
            'shipments' => $shipments,
        ];
    }

    /**
     * Generate container shipment detail report
     */
    public function containerShipmentDetail(string $reference): array
    {
        $parsed = Shipment::parseShippingReference($reference);

        $shipment = Shipment::where('shipping_reference', $reference)
            ->with(['client', 'receivers.items.product', 'expenses.category', 'payments'])
            ->first();

        if (!$shipment) {
            return ['error' => 'Shipment not found'];
        }

        return [
            'reference' => $reference,
            'container_info' => $parsed,
            'client' => [
                'name' => strtoupper($shipment->client?->name ?? 'N/A'),
                'phone' => $shipment->client?->phone,
            ],
            'receivers' => $shipment->receivers->map(fn ($r) => [
                'name' => $r->receiver_name ?: 'SELF',
                'contact' => $r->receiver_phone,
                'location' => strtoupper($r->city ?? $r->address ?? 'N/A'),
                'items' => $r->items,
            ]),
            'financials' => [
                'total' => $shipment->total,
                'paid' => $shipment->paid,
                'balance' => $shipment->total - $shipment->paid,
                'expenses_usd' => $shipment->expenses->sum('amount_usd'),
                'expenses_ghs' => $shipment->expenses->sum('amount_ghs'),
                'net_profit' => $shipment->total - $shipment->expenses->sum('amount_usd'),
            ],
            'expenses' => $shipment->expenses,
            'payments' => $shipment->payments,
        ];
    }

    /**
     * Generate profit and loss report
     */
    public function profitLossReport(Carbon $startDate, Carbon $endDate, ?string $branchId = null): array
    {
        $shipmentQuery = Shipment::whereBetween('created_at', [$startDate, $endDate]);
        $expenseQuery = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        $incomeQuery = Income::where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$startDate, $endDate]);

        if ($branchId) {
            $shipmentQuery->where('branch_id', $branchId);
            $expenseQuery->where('branch_id', $branchId);
            $incomeQuery->where('branch_id', $branchId);
        }

        $shipments = $shipmentQuery->get();
        $expenses = $expenseQuery->get();
        $externalIncome = $incomeQuery->get();

        $payrollQuery = PayrollEntry::whereHas('payrollPeriod', function ($q) use ($startDate, $endDate, $branchId) {
            $q->whereBetween('pay_date', [$startDate, $endDate]);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });
        $payroll = $payrollQuery->sum('net_pay');

        $shipmentRevenue = $shipments->sum('total');
        $externalIncomeTotal = $externalIncome->sum('amount_usd');
        $totalRevenue = $shipmentRevenue + $externalIncomeTotal;

        $shipmentExpenses = $expenses->sum('amount_usd');
        $totalExpenses = $shipmentExpenses + $payroll;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'revenue' => [
                'shipment_revenue' => $shipmentRevenue,
                'external_income' => $externalIncomeTotal,
                'total_revenue' => $totalRevenue,
                'collected' => $shipments->sum('paid') + $externalIncomeTotal,
                'outstanding' => $shipmentRevenue - $shipments->sum('paid'),
            ],
            'expenses' => [
                'shipment_expenses' => $shipmentExpenses,
                'payroll' => $payroll,
                'total' => $totalExpenses,
            ],
            'profit_loss' => [
                'gross_profit' => $totalRevenue - $shipmentExpenses,
                'net_profit' => $totalRevenue - $totalExpenses,
                'margin_percentage' => $totalRevenue > 0
                    ? round((($totalRevenue - $totalExpenses) / $totalRevenue) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Generate client growth report
     */
    public function clientGrowthReport(int $year): array
    {
        $clients = Client::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $monthlyBreakdown = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyBreakdown[$month] = [
                'month' => Carbon::create($year, $month)->format('F'),
                'count' => $clients->get($month)?->count ?? 0,
            ];
        }

        $yearlyTotal = Client::whereYear('created_at', $year)->count();
        $previousYearTotal = Client::whereYear('created_at', $year - 1)->count();

        return [
            'year' => $year,
            'total_new_clients' => $yearlyTotal,
            'previous_year_total' => $previousYearTotal,
            'growth_rate' => $previousYearTotal > 0
                ? round((($yearlyTotal - $previousYearTotal) / $previousYearTotal) * 100, 2)
                : ($yearlyTotal > 0 ? 100 : 0),
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    /**
     * Generate external income report
     */
    public function externalIncomeReport(Carbon $startDate, Carbon $endDate, ?string $branchId = null): array
    {
        $query = Income::where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$startDate, $endDate])
            ->with(['category', 'shipment', 'recordedBy']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $incomes = $query->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_usd' => $incomes->sum('amount_usd'),
            'total_ghs' => $incomes->sum('amount_ghs'),
            'by_category' => $incomes->groupBy('category.name')->map(fn ($items) => [
                'count' => $items->count(),
                'total_usd' => $items->sum('amount_usd'),
                'total_ghs' => $items->sum('amount_ghs'),
            ]),
            'by_payment_method' => $incomes->groupBy('payment_method')->map(fn ($items) => [
                'count' => $items->count(),
                'total_usd' => $items->sum('amount_usd'),
            ]),
            'entries' => $incomes,
        ];
    }

    /**
     * Generate receivables aging report
     */
    public function receivablesAgingReport(?string $branchId = null): array
    {
        $query = Shipment::whereRaw('total > paid');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $shipments = $query->with('client')->get();

        $aging = [
            '0-30' => ['count' => 0, 'amount' => 0],
            '31-60' => ['count' => 0, 'amount' => 0],
            '61-90' => ['count' => 0, 'amount' => 0],
            '90+' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($shipments as $shipment) {
            $balance = $shipment->total - $shipment->paid;
            $daysOutstanding = $shipment->created_at->diffInDays(now());

            if ($daysOutstanding <= 30) {
                $aging['0-30']['count']++;
                $aging['0-30']['amount'] += $balance;
            } elseif ($daysOutstanding <= 60) {
                $aging['31-60']['count']++;
                $aging['31-60']['amount'] += $balance;
            } elseif ($daysOutstanding <= 90) {
                $aging['61-90']['count']++;
                $aging['61-90']['amount'] += $balance;
            } else {
                $aging['90+']['count']++;
                $aging['90+']['amount'] += $balance;
            }
        }

        return [
            'total_outstanding' => $shipments->sum(fn ($s) => $s->total - $s->paid),
            'total_shipments' => $shipments->count(),
            'aging' => $aging,
            'shipments' => $shipments,
        ];
    }
}
