<?php

namespace App\Http\Middleware;

use App\Models\Investment;
use App\Models\Investor;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Services\Audit\Audit;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records who opened or downloaded a document route (invoices, receipts, labels, investor
 * agreements and statements). Several of these routes need no login, so guests are recorded
 * by IP — which also shows when an email link scanner opened a signed link.
 */
class AuditDownload
{
    /**
     * Routes whose `{id}` / `{record}` parameter is a plain key, and the model it names.
     *
     * @var array<string, class-string<Model>>
     */
    private const PLAIN_KEY_MODELS = [
        'packing-slip' => Shipment::class,
        'shipping-label' => Shipment::class,
        'shipping-invoice' => Shipment::class,
        'shipping-invoice-pdf' => Shipment::class,
        'shipping-invoice-download' => Shipment::class,
        'send-invoice-email' => Shipment::class,
        'shipping-receipt' => Shipment::class,
        'delivery-report' => Shipment::class,
        'delivery-report-download' => Shipment::class,
        'print-quotation' => Quotation::class,
        'investor-report-shipments' => Investor::class,
        'investment-agreement' => Investment::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isSuccessful() || $response->isRedirection()) {
            $route = $request->route();
            $routeName = (string) ($route?->getName() ?? $request->path());
            $parameters = $route?->parameters() ?? [];

            Audit::download(
                Str::headline(str_replace(['.', '-', '_'], ' ', $routeName)),
                $this->subject($routeName, $parameters),
                [
                    'route' => $routeName,
                    'parameters' => collect($parameters)
                        ->map(fn ($parameter) => $parameter instanceof Model ? (string) $parameter->getKey() : (is_scalar($parameter) ? (string) $parameter : null))
                        ->all(),
                ],
            );
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function subject(string $routeName, array $parameters): ?Model
    {
        $bound = collect($parameters)->first(fn ($parameter) => $parameter instanceof Model);

        if ($bound instanceof Model) {
            return $bound;
        }

        $model = self::PLAIN_KEY_MODELS[$routeName] ?? null;
        $key = $parameters['id'] ?? $parameters['record'] ?? null;

        if ($model === null || ! is_scalar($key)) {
            return null;
        }

        return $model::query()->find($key);
    }
}
