<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExchangeRateLog;
use App\Service\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_exchange::rate::log'), 403);

        $paginated = ExchangeRateLog::with('recordedBy:id,name')
            ->latest()
            ->paginate((int) $request->input('per_page', 30));

        return $this->paginated($paginated, fn ($r) => $this->formatRate($r));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_exchange::rate::log'), 403);

        $request->validate([
            'from_currency' => 'required|string|max:10',
            'to_currency' => 'required|string|max:10',
            'rate' => 'required|numeric|min:0',
            'source' => 'nullable|string|max:100',
            'rate_date' => 'nullable|date|before_or_equal:today',
        ]);

        // One rate per currency pair per day (unique index): logging again replaces that day's rate.
        $rate = ExchangeRateLog::updateOrCreate(
            [
                'from_currency' => strtoupper($request->from_currency),
                'to_currency' => strtoupper($request->to_currency),
                'rate_date' => $request->input('rate_date', today()->toDateString()),
            ],
            [
                'rate' => $request->rate,
                'source' => $request->input('source', 'manual'),
                'recorded_by' => auth()->id(),
            ]
        );

        return $this->success($this->formatRate($rate), 'Exchange rate logged.', 201);
    }

    /**
     * The rate shipments and payments use (ExchangeRateService::getCurrentRate):
     * the newest rate dated today or earlier, a staff rate winning on the same day.
     */
    public function current(ExchangeRateService $exchangeRates): JsonResponse
    {
        $rate = ExchangeRateLog::where('from_currency', 'USD')
            ->where('to_currency', 'GHS')
            ->where('rate_date', '<=', today())
            ->orderBy('rate_date', 'desc')
            ->orderByRaw("CASE WHEN source IN ('".implode("','", ExchangeRateService::STAFF_SOURCES)."') THEN 0 ELSE 1 END")
            ->first();

        if (! $rate) {
            return $this->success(['rate' => $exchangeRates->getCurrentRate('USD', 'GHS'), 'from_currency' => 'USD', 'to_currency' => 'GHS', 'source' => 'default']);
        }

        return $this->success($this->formatRate($rate));
    }

    private function formatRate(ExchangeRateLog $r): array
    {
        return [
            'id' => $r->id,
            'from_currency' => $r->from_currency,
            'to_currency' => $r->to_currency,
            'rate' => $r->rate,
            'source' => $r->source,
            'recorded_by' => $r->recordedBy ? ['id' => $r->recordedBy->id, 'name' => $r->recordedBy->name] : null,
            'created_at' => $r->created_at,
        ];
    }
}
