<?php

namespace App\Service;

use Carbon\Carbon;
use App\Models\Client;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Shipment;
use App\Enums\IncomeStatus;
use App\Models\PayrollEntry;

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
    public function shipmentsByContainer(int $year, ?int $containerSequence = null)
    {
        $yearSuffix = substr((string) $year, -2);

        $query = Shipment::where('shipping_reference', 'like', "%-{$yearSuffix}-%")
            ->with(['client', 'receivers.items', 'expenses', 'payments']);

        if ($containerSequence !== null) {
            $query->where('container_number', $containerSequence);
        }

        return $query->get();
    }

    /**
     * Generate shipments by year report
     */
    public function shipmentsByYear(int $year)
    {
        $yearSuffix = substr((string) $year, -2);

        return Shipment::where('shipping_reference', 'like', "%-{$yearSuffix}-%")
            ->with(['client', 'expenses'])
            ->get();
    }

    /**
     * Generate shipments by container sequence report (profit/loss)
     */
    public function shipmentsByContainerSequence(int $year, ?int $sequence = null)
    {
        $yearSuffix = substr((string) $year, -2);

        logger("Year Suffix: {$yearSuffix}, Sequence: " . ($sequence ?? 'null'));

        $query = Shipment::where('shipping_reference', 'like', "%-{$yearSuffix}-%")
            ->with(['client', 'receivers', 'expenses']);

        if ($sequence !== null) {
            $query->where('container_number', $sequence);
        }

        $shipments = $query->get();

        logger($shipments);

        // Group by container sequence
        $grouped = $shipments->groupBy('container_number');

        return $grouped->map(function ($containerShipments, $seq) {
            $revenue = $containerShipments->sum('total');
            $expenses = $containerShipments->sum(fn ($s) => $s->expenses->sum('amount_usd'));

            $clients = $containerShipments->groupBy('client_id')->map(function ($clientShipments) {
                $client = $clientShipments->first()->client;
                $total = $clientShipments->sum('total');
                $paid = $clientShipments->sum('paid');
                $balance = $total - $paid;
                return [
                    'name' => $client?->company_name ?? $client?->name ?? 'N/A',
                    'phone' => $client?->phone ?? '',
                    'shipment_count' => $clientShipments->count(),
                    'total' => $total,
                    'paid' => $paid,
                    'balance' => $balance,
                    'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ];
            })->values();

            return [
                'container' => "CON{$seq}",
                'shipment_count' => $containerShipments->count(),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $revenue - $expenses,
                'clients' => $clients,
            ];
        })->values();
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

    /**
     * Generate client shipment history report
     */
    public function clientShipmentHistory(int|string $clientId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = Shipment::where('client_id', $clientId)
            ->with(['client', 'receivers.items.product', 'expenses.category', 'payments']);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
