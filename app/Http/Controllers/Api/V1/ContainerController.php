<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Models\ShipmentContainer;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\V1\BaseApiController;

class ContainerController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $query = ShipmentContainer::query();

        if ($request->boolean('cleared')) {
            $query->cleared();
        } elseif ($request->boolean('pending')) {
            $query->pending();
        }

        //logger($request);

        //logger('container');

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        //logger($paginated);

        return $this->paginated($paginated, fn ($c) => $this->formatContainer($c));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $container = ShipmentContainer::findOrFail($id);

        return $this->success($this->formatContainer($container));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        $container = ShipmentContainer::findOrFail($id);

        $request->validate([
            'is_cleared' => 'sometimes|boolean',
            'review'     => 'nullable|string',
        ]);

        $container->update($request->only(['is_cleared', 'review']));

        return $this->success($this->formatContainer($container->fresh()));
    }

    public function shipments(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $container = ShipmentContainer::findOrFail($id);

        $paginated = Shipment::where('container_number', $container->container_number)
            ->with(['client:id,name', 'destinationBranch:id,name'])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($s) => [
            'id'                  => $s->id,
            'shipping_reference'  => $s->shipping_reference,
            'status'              => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'payment_status'      => $s->payment_status,
            'total'               => $s->total,
            'client'              => $s->client ? ['id' => $s->client->id, 'name' => $s->client->name] : null,
            'destination_branch'  => $s->destinationBranch?->name,
        ]);
    }

    // ── Shipment-container routes ──────────────────────────────────────────────

    /**
     * GET /v1/shipment-containers
     * Returns distinct container numbers from shipments with their cleared status.
     */
    public function containerNumbers(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $numbers = Shipment::whereNotNull('container_number')
            ->select('container_number')
            ->distinct()
            ->orderByDesc('container_number')
            ->pluck('container_number');

        $statuses = ShipmentContainer::whereIn('container_number', $numbers)
            ->get()
            ->keyBy('container_number');

        $data = $numbers->map(function (int $cn) use ($statuses) {
            $record = $statuses->get($cn);
            return [
                'container_number' => $cn,
                'is_cleared'       => (bool) ($record?->is_cleared ?? false),
                'review'           => $record?->review ?? null,
                'shipment_count'   => Shipment::where('container_number', $cn)->count(),
            ];
        })->values();

        return $this->success($data);
    }

    /**
     * PUT /v1/shipment-containers/{containerNumber}
     * updateOrCreate a ShipmentContainer record by container_number.
     */
    public function updateByNumber(Request $request, int $containerNumber): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        $request->validate([
            'is_cleared' => 'required|boolean',
            'review'     => 'nullable|string',
        ]);

        // Resolve 2-digit year from first shipment's shipping reference (mirrors Filament action)
        $year     = null;
        $shipment = Shipment::where('container_number', $containerNumber)
            ->whereNotNull('shipping_reference')
            ->first();

        if ($shipment?->shipping_reference && method_exists(Shipment::class, 'parseShippingReference')) {
            $parsed = Shipment::parseShippingReference($shipment->shipping_reference);
            $year   = $parsed['year'] ?? null;
        }

        $container = ShipmentContainer::updateOrCreate(
            ['container_number' => $containerNumber],
            [
                'container_year' => $year,
                'is_cleared'     => $request->boolean('is_cleared'),
                'review'         => $request->input('review'),
            ]
        );

        return $this->success($this->formatContainer($container->fresh()));
    }

    private function formatContainer(ShipmentContainer $c): array
    {
        return [
            'id'               => $c->id,
            'container_number' => $c->container_number,
            'container_year'   => $c->container_year,
            'container_ref'    => $c->container_ref,
            'is_cleared'       => $c->is_cleared,
            'review'           => $c->review,
            'created_at'       => $c->created_at,
        ];
    }
}
