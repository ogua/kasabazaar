<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IncomeStatus;
use App\Exports\ExpensesExport;
use App\Exports\IncomesExport;
use App\Exports\PayrollExport;
use App\Models\Expense;
use App\Models\Income;
use App\Models\PayrollEntry;
use App\Models\Shipment;
use App\Service\FinancialStatementService;
use App\Service\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends BaseApiController
{
    public function __construct(protected ReportService $reportService) {}

    // ── Financial Dashboard ────────────────────────────────────────────────────

    public function financialDashboard(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        $conNum = $request->input('container_number');

        // Income query
        $incomeBase = Income::whereBetween('income_date', [$start, $end])
            ->where('status', IncomeStatus::Received);
        if ($conNum) {
            $incomeBase->whereHas('shipment', fn ($q) => $q->where('container_number', (int) $conNum));
        }

        // Expense query
        $expenseBase = Expense::whereBetween('expense_date', [$start, $end]);
        if ($conNum) {
            $expenseBase->whereHas('shipment', fn ($q) => $q->where('container_number', (int) $conNum));
        }

        // Shipment query
        $shipmentBase = Shipment::whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
        if ($conNum) {
            $shipmentBase->where('container_number', (int) $conNum);
        }

        $totalIncomeGhs = (clone $incomeBase)->sum('amount_ghs');
        $totalIncomeUsd = (clone $incomeBase)->sum('amount_usd');
        $totalExpenseGhs = (clone $expenseBase)->sum('amount_ghs');
        $totalExpenseUsd = (clone $expenseBase)->sum('amount_usd');
        $totalPayroll = PayrollEntry::whereHas('payrollPeriod', fn ($q) => $q->whereBetween('pay_date', [$start, $end]))->sum('net_salary');

        $shipments = (clone $shipmentBase)->get();
        $totalShips = $shipments->count();
        $delivered = $shipments->filter(fn ($s) => $s->status?->value === 'delivered')->count();
        $pending = $shipments->filter(fn ($s) => in_array($s->status?->value, ['pending', 'processing']))->count();
        $activeConts = Shipment::whereNull('delivered_at')
            ->whereNotNull('container_number')
            ->selectRaw('COUNT(DISTINCT container_number) as cnt')
            ->value('cnt') ?? 0;

        // Container profit breakdown
        $containerProfit = Shipment::selectRaw('container_number, COUNT(*) as shipment_count, SUM(total) as income_ghs')
            ->whereNotNull('container_number')
            ->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->groupBy('container_number')
            ->orderBy('container_number')
            ->get()
            ->map(function ($row) use ($start, $end) {
                $expGhs = Expense::whereHas('shipment', fn ($q) => $q->where('container_number', $row->container_number))
                    ->whereBetween('expense_date', [$start, $end])
                    ->sum('amount_ghs');

                return [
                    'container_number' => $row->container_number,
                    'label' => 'CON'.$row->container_number,
                    'income_ghs' => (float) $row->income_ghs,
                    'expense_ghs' => (float) $expGhs,
                    'profit_ghs' => (float) $row->income_ghs - (float) $expGhs,
                    'shipment_count' => (int) $row->shipment_count,
                ];
            })
            ->values();

        // Top delivery states (receivers.state_region)
        $topStates = Shipment::join('receivers', 'shipments.id', '=', 'receivers.shipment_id')
            ->selectRaw('receivers.state_region as state, COUNT(*) as `count`, SUM(shipments.total) as revenue_ghs')
            ->whereNotNull('receivers.state_region')
            ->whereBetween('shipments.created_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->groupBy('receivers.state_region')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'state' => $r->state,
                'count' => (int) $r->count,
                'revenue_ghs' => (float) $r->revenue_ghs,
            ])
            ->values();

        // Monthly trend — last 12 months relative to end date
        $monthlyTrend = collect(range(11, 0))->map(function ($i) use ($end) {
            $month = Carbon::parse($end)->subMonths($i);
            $monthStart = $month->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd = $month->copy()->endOfMonth()->format('Y-m-d');

            return [
                'month' => $month->format('M'),
                'income_ghs' => (float) Income::whereBetween('income_date', [$monthStart, $monthEnd])
                    ->where('status', IncomeStatus::Received)->sum('amount_ghs'),
                'expense_ghs' => (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount_ghs'),
            ];
        })->values();

        return $this->success([
            'overview' => [
                'total_income_ghs' => (float) $totalIncomeGhs,
                'total_expense_ghs' => (float) $totalExpenseGhs,
                'total_payroll_ghs' => (float) $totalPayroll,
                'net_profit_ghs' => (float) $totalIncomeGhs - $totalExpenseGhs - $totalPayroll,
                'total_income_usd' => (float) $totalIncomeUsd,
                'total_expense_usd' => (float) $totalExpenseUsd,
                'net_profit_usd' => (float) $totalIncomeUsd - $totalExpenseUsd,
            ],
            'kpis' => [
                'total_shipments' => $totalShips,
                'delivered_shipments' => $delivered,
                'pending_shipments' => $pending,
                'active_containers' => (int) $activeConts,
                'delivery_rate' => $totalShips > 0 ? round(($delivered / $totalShips) * 100, 1) : 0,
            ],
            'container_profit' => $containerProfit,
            'top_states' => $topStates,
            'monthly_trend' => $monthlyTrend,
        ]);
    }

    // ── Expenses ───────────────────────────────────────────────────────────────

    public function expenses(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);

        $paginated = Expense::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $thisTotal = Expense::whereBetween('expense_date', [$start, $end])->sum('amount_ghs');
        $thisTotalUsd = Expense::whereBetween('expense_date', [$start, $end])->sum('amount_usd');

        $days = Carbon::parse($start)->diffInDays(Carbon::parse($end));
        $prevEnd = Carbon::parse($start)->subDay()->format('Y-m-d');
        $prevStart = Carbon::parse($prevEnd)->subDays($days)->format('Y-m-d');
        $lastTotal = Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount_ghs');
        $lastTotalUsd = Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount_usd');
        $growth = $lastTotal > 0 ? round((($thisTotal - $lastTotal) / $lastTotal) * 100, 2) : 0;

        $byCategory = Expense::with('category')
            ->whereBetween('expense_date', [$start, $end])
            ->get()
            ->groupBy(fn ($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn ($g, $k) => [
                'category' => $k,
                'count' => $g->count(),
                'total_usd' => (float) $g->sum('amount_usd'),
                'total_ghs' => (float) $g->sum('amount_ghs'),
            ])
            ->sortByDesc('total_ghs')
            ->values();

        $byStage = Expense::whereBetween('expense_date', [$start, $end])
            ->get()
            ->groupBy(fn ($e) => $e->expense_stage?->value ?? 'N/A')
            ->map(fn ($g, $k) => [
                'stage' => $k,
                'count' => $g->count(),
                'total_usd' => (float) $g->sum('amount_usd'),
                'total_ghs' => (float) $g->sum('amount_ghs'),
            ])
            ->values();

        $items = $paginated->getCollection()->map(fn ($e) => [
            'id' => $e->id,
            'reference' => $e->reference,
            'date' => $e->expense_date?->format('Y-m-d'),
            'category' => $e->category?->name ?? 'Uncategorized',
            'description' => $e->description,
            'amount_usd' => (float) $e->amount_usd,
            'amount_ghs' => (float) $e->amount_ghs,
            'exchange_rate' => (float) $e->exchange_rate,
            'branch' => $e->branch?->name ?? 'N/A',
            'shipment_ref' => $e->shipment?->shipping_reference ?? 'General',
            'recorded_by' => $e->recordedBy?->name ?? 'System',
            'expense_stage' => $e->expense_stage?->value ?? 'N/A',
        ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'this_period_ghs' => (float) $thisTotal,
                'last_period_ghs' => (float) $lastTotal,
                'this_period_usd' => (float) $thisTotalUsd,
                'last_period_usd' => (float) $lastTotalUsd,
                'growth_percent' => $growth,
                'total_count' => $paginated->total(),
                'by_category' => $byCategory,
                'by_stage' => $byStage,
                'start_date' => $start,
                'end_date' => $end,
            ],
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function exportExpenses(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');
        $file = "expense-report-{$start}-to-{$end}";

        if ($format === 'excel') {
            return Excel::download(new ExpensesExport($start, $end), "{$file}.xlsx");
        }

        $expenses = Expense::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.expense-pdf', compact('expenses', 'start', 'end'));

        return response()->streamDownload(fn () => print ($pdf->output()), "{$file}.pdf");
    }

    // ── Incomes ────────────────────────────────────────────────────────────────

    public function incomes(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);

        $paginated = Income::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('income_date', [$start, $end])
            ->orderBy('income_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $received = fn ($s, $e) => Income::whereBetween('income_date', [$s, $e])
            ->where('status', IncomeStatus::Received);

        $thisGhs = $received($start, $end)->sum('amount_ghs');
        $thisUsd = $received($start, $end)->sum('amount_usd');

        $days = Carbon::parse($start)->diffInDays(Carbon::parse($end));
        $prevEnd = Carbon::parse($start)->subDay()->format('Y-m-d');
        $prevStart = Carbon::parse($prevEnd)->subDays($days)->format('Y-m-d');
        $lastGhs = $received($prevStart, $prevEnd)->sum('amount_ghs');
        $lastUsd = $received($prevStart, $prevEnd)->sum('amount_usd');
        $growth = $lastGhs > 0 ? round((($thisGhs - $lastGhs) / $lastGhs) * 100, 2) : 0;

        $byCategory = Income::with('category')
            ->where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$start, $end])
            ->get()
            ->groupBy(fn ($i) => $i->category?->name ?? 'Uncategorized')
            ->map(fn ($g, $k) => [
                'category' => $k,
                'count' => $g->count(),
                'total_usd' => (float) $g->sum('amount_usd'),
                'total_ghs' => (float) $g->sum('amount_ghs'),
            ])
            ->sortByDesc('total_ghs')
            ->values();

        $byMethod = Income::where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$start, $end])
            ->get()
            ->groupBy(fn ($i) => $i->payment_method?->value ?? 'N/A')
            ->map(fn ($g, $k) => [
                'method' => $k,
                'count' => $g->count(),
                'total_usd' => (float) $g->sum('amount_usd'),
                'total_ghs' => (float) $g->sum('amount_ghs'),
            ])
            ->values();

        $items = $paginated->getCollection()->map(fn ($i) => [
            'id' => $i->id,
            'reference' => $i->reference,
            'date' => $i->income_date?->format('Y-m-d'),
            'category' => $i->category?->name ?? 'Uncategorized',
            'description' => $i->description,
            'amount_usd' => (float) $i->amount_usd,
            'amount_ghs' => (float) $i->amount_ghs,
            'exchange_rate' => (float) $i->exchange_rate,
            'branch' => $i->branch?->name ?? 'N/A',
            'shipment_ref' => $i->shipment?->shipping_reference ?? 'External',
            'recorded_by' => $i->recordedBy?->name ?? 'System',
            'status' => $i->status?->value ?? 'N/A',
            'payment_method' => $i->payment_method?->value ?? 'N/A',
        ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'this_period_ghs' => (float) $thisGhs,
                'last_period_ghs' => (float) $lastGhs,
                'this_period_usd' => (float) $thisUsd,
                'last_period_usd' => (float) $lastUsd,
                'growth_percent' => $growth,
                'total_count' => $paginated->total(),
                'by_category' => $byCategory,
                'by_method' => $byMethod,
                'start_date' => $start,
                'end_date' => $end,
            ],
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function exportIncomes(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');
        $file = "income-report-{$start}-to-{$end}";

        if ($format === 'excel') {
            return Excel::download(new IncomesExport($start, $end), "{$file}.xlsx");
        }

        $incomes = Income::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('income_date', [$start, $end])
            ->orderBy('income_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.income-pdf', compact('incomes', 'start', 'end'));

        return response()->streamDownload(fn () => print ($pdf->output()), "{$file}.pdf");
    }

    // ── Payroll ────────────────────────────────────────────────────────────────

    public function payroll(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);

        $inPeriod = fn ($q) => $q->whereHas('payrollPeriod', fn ($p) => $p->whereBetween('pay_date', [$start, $end]));

        $paginated = PayrollEntry::with(['payrollPeriod', 'employee'])
            ->whereHas('payrollPeriod', fn ($p) => $p->whereBetween('pay_date', [$start, $end]))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $thisNet = $inPeriod(PayrollEntry::query())->sum('net_salary');
        $days = Carbon::parse($start)->diffInDays(Carbon::parse($end));
        $prevEnd = Carbon::parse($start)->subDay()->format('Y-m-d');
        $prevStart = Carbon::parse($prevEnd)->subDays($days)->format('Y-m-d');
        $lastNet = PayrollEntry::whereHas('payrollPeriod', fn ($p) => $p->whereBetween('pay_date', [$prevStart, $prevEnd]))->sum('net_salary');
        $growth = $lastNet > 0 ? round((($thisNet - $lastNet) / $lastNet) * 100, 2) : 0;

        $allEntries = $inPeriod(PayrollEntry::query())->get();
        $totalEmp = $inPeriod(PayrollEntry::query())->selectRaw('COUNT(DISTINCT staff_id) as cnt')->value('cnt') ?? 0;
        $avgSalary = $totalEmp > 0 ? $thisNet / $totalEmp : 0;
        $totalDeduct = $allEntries->sum('total_deductions');
        $totalBonus = $allEntries->sum('bonus');

        $byStatus = $allEntries
            ->groupBy(fn ($p) => $p->status?->value ?? 'N/A')
            ->map(fn ($g, $k) => [
                'status' => $k,
                'count' => $g->count(),
                'total' => (float) $g->sum('net_salary'),
                'avg' => (float) $g->avg('net_salary'),
            ])
            ->values();

        $items = $paginated->getCollection()->map(fn ($e) => [
            'id' => $e->id,
            'employee' => $e->employee?->name ?? 'N/A',
            'period' => $e->payrollPeriod
                ? $e->payrollPeriod->start_date->format('M d').' - '.$e->payrollPeriod->end_date->format('M d, Y')
                : 'N/A',
            'pay_date' => $e->payrollPeriod?->pay_date?->format('Y-m-d'),
            'gross_salary' => (float) $e->gross_pay,
            'deductions' => (float) $e->total_deductions,
            'bonuses' => (float) $e->bonus,
            'net_salary' => (float) $e->net_salary,
            'status' => $e->status?->value ?? 'N/A',
        ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'this_period' => (float) $thisNet,
                'last_period' => (float) $lastNet,
                'growth_percent' => $growth,
                'total_employees' => (int) $totalEmp,
                'avg_salary' => (float) $avgSalary,
                'total_deductions' => (float) $totalDeduct,
                'total_bonuses' => (float) $totalBonus,
                'total_count' => $paginated->total(),
                'by_status' => $byStatus,
                'start_date' => $start,
                'end_date' => $end,
            ],
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function exportPayroll(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        $format = $request->input('format', 'pdf');
        $file = "payroll-report-{$start}-to-{$end}";

        if ($format === 'excel') {
            return Excel::download(new PayrollExport($start, $end), "{$file}.xlsx");
        }

        $payrolls = PayrollEntry::with(['payrollPeriod', 'employee'])
            ->whereHas('payrollPeriod', fn ($p) => $p->whereBetween('pay_date', [$start, $end]))
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.payroll-pdf', compact('payrolls', 'start', 'end'));

        return response()->streamDownload(fn () => print ($pdf->output()), "{$file}.pdf");
    }

    // ── Shipments ─────────────────────────────────────────────────────────────

    public function shipments(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $type = $request->input('report_type');
        $year = (int) $request->input('year', now()->year);
        $seq = $request->input('container_sequence') ? (int) $request->input('container_sequence') : null;
        $clientId = $request->input('client_id');

        $data = match ($type) {
            'by_container' => $this->reportService->shipmentsByContainer($year, $seq),
            'by_year' => $this->reportService->shipmentsByYear($year),
            'profit_loss' => $this->reportService->shipmentsByContainerSequence($year, $seq),
            'client_shipments' => $this->reportService->clientShipmentHistory(
                $clientId,
                $request->input('start_date'),
                $request->input('end_date')
            ),
            default => collect(),
        };

        // Normalize to plain arrays matching the mobile spec
        $normalized = $this->normalizeShipmentData($type, $data);

        $titles = [
            'by_container' => 'Shipments by Container Report',
            'by_year' => 'Shipments by Year Report',
            'profit_loss' => 'Profit / Loss Report',
            'client_shipments' => 'Client Shipment History Report',
        ];

        return $this->success([
            'report_type' => $type,
            'title' => $titles[$type] ?? 'Shipment Report',
            'generated_at' => now()->toIso8601String(),
            'data' => $normalized,
        ]);
    }

    public function exportShipments(Request $request)
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $type = $request->input('report_type', 'by_year');
        $format = $request->input('format', 'pdf');
        $year = (int) $request->input('year', now()->year);
        $seq = $request->input('container_sequence') ? (int) $request->input('container_sequence') : null;

        $data = match ($type) {
            'by_container' => $this->reportService->shipmentsByContainer($year, $seq),
            'by_year' => $this->reportService->shipmentsByYear($year),
            'profit_loss' => $this->reportService->shipmentsByContainerSequence($year, $seq),
            'client_shipments' => $this->reportService->clientShipmentHistory(
                $request->input('client_id'),
                $request->input('start_date'),
                $request->input('end_date')
            ),
            default => collect(),
        };

        $titles = [
            'by_container' => 'Shipments by Container Report',
            'by_year' => 'Shipments by Year Report',
            'profit_loss' => 'Profit Loss Report',
            'client_shipments' => 'Client Shipment History Report',
        ];
        $title = $titles[$type] ?? 'Shipment Report';
        $file = str_replace([' ', '/'], '_', strtolower($title)).'_'.now()->format('Y-m-d');

        if ($format === 'excel') {
            return Excel::download(new \App\Exports\ShipmentReportExport($data, $type), "{$file}.xlsx");
        }

        $pdf = Pdf::loadView('reports.shipment-report-pdf', [
            'data' => $data,
            'reportType' => $type,
            'title' => $title,
            'year' => $year,
            'containerSequence' => $seq,
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), "{$file}.pdf");
    }

    // ── Additional ReportService endpoints ────────────────────────────────────

    public function profitLossSummary(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $start = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $end = Carbon::parse($request->input('end_date', now()));
        $branchId = $request->input('branch_id');

        return $this->success($this->reportService->profitLossReport($start, $end, $branchId));
    }

    public function clientGrowth(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $year = (int) $request->input('year', now()->year);

        return $this->success($this->reportService->clientGrowthReport($year));
    }

    public function receivablesAging(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $branchId = $request->input('branch_id');
        $result = $this->reportService->receivablesAgingReport($branchId);

        return $this->success([
            'total_outstanding' => (float) $result['total_outstanding'],
            'total_shipments' => (int) $result['total_shipments'],
            'aging' => $result['aging'],
        ]);
    }

    // ── Statutory statements ──────────────────────────────────────────────────

    public function profitAndLoss(Request $request, FinancialStatementService $service): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        return $this->success($service->profitAndLoss(
            (int) $request->input('year', now()->format('Y'))
        ));
    }

    public function balanceSheet(Request $request, FinancialStatementService $service): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        return $this->success($service->balanceSheet(
            (int) $request->input('year', now()->format('Y'))
        ));
    }

    public function accountsReceivable(Request $request, FinancialStatementService $service): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $year = (int) $request->input('year', now()->format('Y'));

        return $this->success($service->accountsReceivable(
            $year,
            $request->filled('as_of') ? Carbon::parse($request->input('as_of')) : null
        ));
    }

    public function containerDetail(Request $request, string $reference): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $detail = $this->reportService->containerShipmentDetail($reference);

        if (isset($detail['error'])) {
            return $this->error($detail['error'], 404);
        }

        return $this->success($detail);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Normalize raw Eloquent collections returned by ReportService into plain
     * arrays that match the TypeScript interfaces defined in the mobile spec.
     */
    private function normalizeShipmentData(string $type, $data): array
    {
        if ($type === 'by_container') {
            // ReportService::shipmentsByContainer returns raw Shipment models
            // Group by container_number to produce ByContainerItem[]
            return $data->groupBy('container_number')->map(function ($shipments, $conNum) {
                $yearSuffix = $shipments->first()?->shipping_reference
                    ? explode('-', $shipments->first()->shipping_reference)[2] ?? null
                    : null;
                $year = $yearSuffix ? (int) ('20'.$yearSuffix) : now()->year;

                $revenue = (float) $shipments->sum('total');
                $expenses = (float) $shipments->sum(fn ($s) => $s->expenses->sum('amount_ghs'));
                $states = $shipments->flatMap(fn ($s) => $s->receivers->pluck('state_region'))
                    ->filter()->unique()->values()->toArray();

                return [
                    'container_number' => (int) $conNum,
                    'label' => 'CON'.$conNum,
                    'year' => $year,
                    'shipment_count' => $shipments->count(),
                    'total_revenue_ghs' => $revenue,
                    'total_expense_ghs' => $expenses,
                    'profit_ghs' => $revenue - $expenses,
                    'states' => $states,
                ];
            })->values()->toArray();
        }

        if ($type === 'by_year') {
            // ReportService::shipmentsByYear returns raw Shipment models
            // Group by month to produce ByYearItem[]
            return $data->groupBy(fn ($s) => $s->created_at->month)->map(function ($shipments, $month) {
                $revenue = (float) $shipments->sum('total');
                $expenses = (float) $shipments->sum(fn ($s) => $s->expenses->sum('amount_ghs'));
                $year = $shipments->first()->created_at->year;

                return [
                    'month' => Carbon::create($year, $month)->format('F'),
                    'month_number' => (int) $month,
                    'year' => $year,
                    'shipment_count' => $shipments->count(),
                    'revenue_ghs' => $revenue,
                    'expense_ghs' => $expenses,
                    'profit_ghs' => $revenue - $expenses,
                ];
            })->sortBy('month_number')->values()->toArray();
        }

        if ($type === 'profit_loss') {
            // ReportService::shipmentsByContainerSequence returns already-processed array
            // Map to ProfitLossItem[] (per-shipment view)
            if ($data->isEmpty()) {
                return [];
            }

            // Flatten containers → individual shipments
            return $data->flatMap(function ($container) {
                // The service returns grouped container data; expand back to per-shipment
                return collect($container['clients'] ?? [])->map(fn ($c) => [
                    'shipping_reference' => 'CON'.($container['container'] ?? '?'),
                    'client' => $c['name'] ?? 'N/A',
                    'origin' => 'N/A',
                    'destination' => 'N/A',
                    'status' => $c['payment_status'] ?? 'N/A',
                    'revenue_ghs' => (float) ($c['total'] ?? 0),
                    'expense_ghs' => (float) ($container['expenses'] ?? 0),
                    'profit_ghs' => (float) (($c['total'] ?? 0) - ($container['expenses'] ?? 0)),
                    'profit_margin' => ($c['total'] ?? 0) > 0
                        ? round((($c['total'] - ($container['expenses'] ?? 0)) / $c['total']) * 100, 1)
                        : 0,
                ]);
            })->values()->toArray();
        }

        if ($type === 'client_shipments') {
            // ReportService::clientShipmentHistory returns raw Shipment models
            return $data->map(fn ($s) => [
                'shipping_reference' => $s->shipping_reference,
                'status' => $s->status?->value ?? 'N/A',
                'origin' => $s->origin ?? 'N/A',
                'destination' => $s->receivers->first()?->city ?? 'N/A',
                'shipped_at' => $s->created_at?->format('Y-m-d'),
                'delivered_at' => $s->delivered_at?->format('Y-m-d'),
                'total_ghs' => (float) $s->total,
                'total_usd' => (float) ($s->total_usd ?? 0),
                'items_count' => $s->receivers->sum(fn ($r) => $r->items->count()),
            ])->values()->toArray();
        }

        return $data->values()->toArray();
    }
}
