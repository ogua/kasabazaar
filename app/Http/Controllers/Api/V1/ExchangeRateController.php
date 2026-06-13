<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExchangeRateLog;
use App\Http\Controllers\Api\V1\BaseApiController;
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
        abort_unless(auth()->user()->can('create_exchange_rate_log'), 403);

        $request->validate([
            'from_currency' => 'required|string|max:10',
            'to_currency'   => 'required|string|max:10',
            'rate'          => 'required|numeric|min:0',
            'source'        => 'nullable|string|max:100',
        ]);

        $rate = ExchangeRateLog::create([
            'from_currency' => strtoupper($request->from_currency),
            'to_currency'   => strtoupper($request->to_currency),
            'rate'          => $request->rate,
            'source'        => $request->input('source', 'manual'),
            'recorded_by'   => auth()->id(),
        ]);

        return $this->success($this->formatRate($rate), 'Exchange rate logged.', 201);
    }

    public function current(): JsonResponse
    {
        $rate = ExchangeRateLog::where('from_currency', 'USD')
            ->where('to_currency', 'GHS')
            ->latest()
            ->first();

        if (!$rate) {
            return $this->success(['rate' => 12.0, 'from_currency' => 'USD', 'to_currency' => 'GHS', 'source' => 'default']);
        }

        return $this->success($this->formatRate($rate));
    }

    private function formatRate(ExchangeRateLog $r): array
    {
        return [
            'id'            => $r->id,
            'from_currency' => $r->from_currency,
            'to_currency'   => $r->to_currency,
            'rate'          => $r->rate,
            'source'        => $r->source,
            'recorded_by'   => $r->recordedBy ? ['id' => $r->recordedBy->id, 'name' => $r->recordedBy->name] : null,
            'created_at'    => $r->created_at,
        ];
    }
}
