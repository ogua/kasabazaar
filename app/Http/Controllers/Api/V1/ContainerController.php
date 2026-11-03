<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\BulkContainerStatusRequest;
use App\Jobs\BulkContainerShipmentStatusJob;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Services\ContainerClearanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        // logger($request);

        // logger('container');

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        // logger($paginated);

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
            'review' => 'nullable|string',
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
            'id' => $s->id,
            'shipping_reference' => $s->shipping_reference,
            'status' => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'payment_status' => $s->payment_status,
            'total' => $s->total,
            'client' => $s->client ? ['id' => $s->client->id, 'name' => $s->client->name] : null,
            'destination_branch' => $s->destinationBranch?->name,
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
                'is_cleared' => (bool) ($record?->is_cleared ?? false),
                'review' => $record?->review ?? null,
                'shipment_count' => Shipment::where('container_number', $cn)->count(),
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

        // The clearing agent is optional only so app builds released before it was
        // asked for keep working; current builds always send it when clearing.
        $request->validate([
            'is_cleared' => 'required|boolean',
            'review' => 'nullable|string',
            'clearing_agent_id' => 'nullable|exists:clearing_agents,id',
            'notify_clients' => 'nullable|boolean',
        ]);

        $container = app(ContainerClearanceService::class)->record(
            $containerNumber,
            $request->boolean('is_cleared'),
            $request->input('clearing_agent_id'),
            $request->input('review'),
            $request->boolean('notify_clients', true),
            $request->user()->id,
        );

        return $this->success($this->formatContainer($container->fresh()));
    }

    /**
     * POST /v1/shipment-containers/{containerNumber}/bulk-status
     * Dispatch a queued job to update all shipments in a container to a new status.
     */
    public function bulkStatusUpdate(BulkContainerStatusRequest $request, int $containerNumber): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        $count = Shipment::query()->where('container_number', '=', $containerNumber)->count();

        if ($count === 0) {
            return $this->error('No shipments found for this container.', 404);
        }

        $notifyClients = $request->boolean('notify_clients', true);

        BulkContainerShipmentStatusJob::dispatch(
            containerNumber: $containerNumber,
            newStatus: $request->input('status'),
            note: $request->input('note'),
            notifyClients: $notifyClients,
        );

        return $this->success([
            'container_number' => $containerNumber,
            'shipment_count' => $count,
            'new_status' => $request->input('status'),
            'notify_clients' => $notifyClients,
        ], "Bulk status update queued for {$count} shipments in CON{$containerNumber}.");
    }

    private function formatContainer(ShipmentContainer $c): array
    {
        return [
            'id' => $c->id,
            'container_number' => $c->container_number,
            'container_year' => $c->container_year,
            'container_ref' => $c->container_ref,
            'is_cleared' => $c->is_cleared,
            'review' => $c->review,
            'clearing_agent_id' => $c->clearing_agent_id,
            'cleared_at' => $c->cleared_at,
            'created_at' => $c->created_at,
        ];
    }
}
