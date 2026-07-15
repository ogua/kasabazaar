<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Service\InvestorCompanyPerformanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvestorReportController extends Controller
{
    public function shipments(Request $request, Investor $investor, InvestorCompanyPerformanceService $service)
    {
        return $this->stream('reports.investor-shipment-summary-pdf', $this->summaryData($request, $service, 'shipmentSummary'));
    }

    public function shipmentsDownload(Request $request, Investor $investor, InvestorCompanyPerformanceService $service)
    {
        return $this->download('reports.investor-shipment-summary-pdf', $this->summaryData($request, $service, 'shipmentSummary'), 'shipment-summary');
    }

    public function income(Request $request, Investor $investor, InvestorCompanyPerformanceService $service)
    {
        return $this->stream('reports.investor-income-summary-pdf', $this->summaryData($request, $service, 'incomeSummary'));
    }

    public function incomeDownload(Request $request, Investor $investor, InvestorCompanyPerformanceService $service)
    {
        return $this->download('reports.investor-income-summary-pdf', $this->summaryData($request, $service, 'incomeSummary'), 'income-summary');
    }

    public function expenses(Request $request, Investor $investor, InvestorCompanyPerformanceService $service)
    {
        return $this->stream('reports.investor-expense-summary-pdf', $this->summaryData($request, $service, 'expenseSummary'));
    }

    public function expensesDownload(Request $request, Investor $investor, InvestorCompanyPerformanceService $service)
    {
        return $this->download('reports.investor-expense-summary-pdf', $this->summaryData($request, $service, 'expenseSummary'), 'expense-summary');
    }

    private function summaryData(Request $request, InvestorCompanyPerformanceService $service, string $method): array
    {
        [$start, $end] = $this->resolvePeriod($request);

        return ['summary' => $service->{$method}($start, $end)];
    }

    private function resolvePeriod(Request $request): array
    {
        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))
            : now()->startOfMonth();

        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))
            : now();

        return [$start, $end];
    }

    private function stream(string $view, array $data)
    {
        return $this->pdf($view, $data)->stream();
    }

    private function download(string $view, array $data, string $filenamePrefix)
    {
        return $this->pdf($view, $data)->download($filenamePrefix.'-'.now()->format('Y-m-d').'.pdf');
    }

    private function pdf(string $view, array $data)
    {
        return Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }
}
