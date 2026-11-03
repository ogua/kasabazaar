<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Service\InvestorCompanyPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class InvestorCompanyPerformanceController extends InvestorBaseController
{
    public function index(InvestorCompanyPerformanceService $service): JsonResponse
    {
        return $this->success($service->monthlyRevenueTrend());
    }

    /**
     * The dashboard's "Company Performance" zone in one call: year-to-date income,
     * costs and shipments, plus the monthly revenue trend. These are the company's
     * figures, not the investor's balance.
     */
    public function overview(InvestorCompanyPerformanceService $service): JsonResponse
    {
        $start = now()->startOfYear();
        $end = now();

        return $this->success([
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'shipments' => $service->shipmentSummary($start, $end),
            'income' => $service->incomeSummary($start, $end),
            'expenses' => $service->expenseSummary($start, $end),
            'monthly_revenue' => $service->monthlyRevenueTrend(),
        ]);
    }

    public function shipments(Request $request, InvestorCompanyPerformanceService $service): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);

        return $this->success([
            'summary' => $service->shipmentSummary($start, $end),
            'pdf_url' => $this->signedReportUrl('investor-report-shipments', $start, $end),
            'pdf_download_url' => $this->signedReportUrl('investor-report-shipments-download', $start, $end),
        ]);
    }

    public function income(Request $request, InvestorCompanyPerformanceService $service): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);

        return $this->success([
            'summary' => $service->incomeSummary($start, $end),
            'pdf_url' => $this->signedReportUrl('investor-report-income', $start, $end),
            'pdf_download_url' => $this->signedReportUrl('investor-report-income-download', $start, $end),
        ]);
    }

    public function expenses(Request $request, InvestorCompanyPerformanceService $service): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);

        return $this->success([
            'summary' => $service->expenseSummary($start, $end),
            'pdf_url' => $this->signedReportUrl('investor-report-expenses', $start, $end),
            'pdf_download_url' => $this->signedReportUrl('investor-report-expenses-download', $start, $end),
        ]);
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

    private function signedReportUrl(string $routeName, Carbon $start, Carbon $end): string
    {
        return URL::temporarySignedRoute($routeName, now()->addDay(), [
            'investor' => $this->investorId(),
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ]);
    }
}
