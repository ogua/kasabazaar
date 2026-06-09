<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\Tracking;
use App\Models\ShipmentItem;
use Illuminate\Http\Request;
use App\Models\ShipmentMedia;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\TrackingResource;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Controllers\Api\V1\BaseApiController;

class ShipmentController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        ///logger('loadig shipments with branch: ' . $request->header('X-Branch-Id'));
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $query    = Shipment::where('branch_id', $branchId)
            ->with(['client:id,name,phone', 'originBranch:id,name', 'destinationBranch:id,name', 'containerStatus:id,container_number,is_cleared,review']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipping_reference', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $perPage   = (int) $request->input('per_page', 20);
        $paginated = $query->latest()->paginate($perPage);

        return $this->paginated($paginated, fn ($s) => $this->formatShipment($s));
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $shipmentType = $request->input('shipment_type', 'new');
        $refData      = Shipment::generateShippingReference(
            $shipmentType,
            $request->input('shipped_at'),
            null,
            $request->input('existing_shipment_id')
        );

        $shipment = Shipment::create([
            'client_id'                 => $request->client_id,
            'branch_id'                 => $branchId,
            'origin_branch_id'          => $request->origin_branch_id,
            'destination_branch_id'     => $request->destination_branch_id,
            'shipping_reference'        => $refData['reference'],
            'tracking_number'           => strtoupper(\Illuminate\Support\Str::random(12)),
            'status'                    => 'pending',
            'payment_status'            => $request->input('payment_status', 'pending'),
            'shipped_at'                => $request->input('shipped_at'),
            'estimated_delivery_date'   => $request->input('estimated_delivery_date'),
            'shipping_cost'             => $request->input('shipping_cost', 0),
            'exchange_rate_at_shipment' => $request->input('exchange_rate_at_shipment'),
            'vat_percentage'            => $request->input('vat_percentage', 0),
            'insurance_accepted'        => $request->input('insurance_accepted', false),
            'insurance'                 => $request->input('insurance', 0),
            'total'                     => $request->input('total', $request->input('shipping_cost', 0)),
            'paid'                      => $request->input('paid', 0),
            'recorded_by'               => auth()->id(),
            'external_token'            => Shipment::generateExternalToken(),
            'client_existence'          => $shipmentType,
        ]);

        // Create receivers and their items
        foreach ($request->input('receivers', []) as $receiverData) {
            $receiver = Receiver::create([
                'shipment_id'         => $shipment->id,
                'receiver_name'       => $receiverData['receiver_name'],
                'receiver_phone'      => $receiverData['receiver_phone'] ?? null,
                'receiver_email'      => $receiverData['receiver_email'] ?? null,
                'country'             => $receiverData['country'] ?? null,
                'state_region'        => $receiverData['state_region'] ?? null,
                'city'                => $receiverData['city'] ?? null,
                'address'             => $receiverData['address'] ?? null,
                'receiver_id_type'    => $receiverData['receiver_id_type'] ?? null,
                'receiver_id_number'  => $receiverData['receiver_id_number'] ?? null,
            ]);

            foreach ($receiverData['items'] ?? [] as $item) {
                ShipmentItem::create([
                    'shipment_id'  => $shipment->id,
                    'receiver_id'  => $receiver->id,
                    'product_id'   => $item['product_id'] ?? null,
                    'quantity'     => $item['quantity'] ?? 1,
                    'item_cost'    => $item['item_cost'] ?? 0,
                    'box_no'       => $item['box_no'] ?? null,
                ]);
            }
        }

        $shipment->load(['client', 'originBranch', 'destinationBranch', 'receivers.items.product']);

        return $this->success($this->formatShipmentDetail($shipment), 'Shipment created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $shipment = Shipment::where('branch_id', $branchId)
            ->with([
                'client',
                'originBranch:id,name',
                'destinationBranch:id,name',
                'receivers.items.product',
                'payments',
                'invoice',
                'media',
                'expenses.category',
            ])
            ->findOrFail($id);

        return $this->success($this->formatShipmentDetail($shipment));
    }

    public function update(UpdateShipmentRequest $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $shipment = Shipment::where('branch_id', $branchId)->findOrFail($id);

        $shipment->update($request->only([
            'status', 'payment_status', 'destination_branch_id',
            'estimated_delivery_date', 'shipping_cost', 'total', 'paid',
            'vat_percentage', 'insurance_accepted', 'insurance',
            'is_received', 'delivered_at',
        ]));

        return $this->success($this->formatShipmentDetail($shipment->fresh(['client', 'originBranch', 'destinationBranch'])));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $shipment = Shipment::where('branch_id', $branchId)->findOrFail($id);
        $shipment->delete();

        return $this->success(null, 'Shipment deleted.');
    }

    public function trackings(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $shipment = Shipment::where('branch_id', $branchId)->findOrFail($id);

        $trackings = Tracking::where('shipment_id', $id)
            ->orderBy('status_updated_at', 'asc')
            ->get();

        return $this->success(TrackingResource::collection($trackings));
    }

    public function addTracking(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $shipment = Shipment::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'status'      => 'required|string',
            'description' => 'nullable|string',
        ]);

        $tracking = Tracking::create([
            'shipment_id'       => $id,
            'branch_id'         => $branchId,
            'status'            => $request->status,
            'description'       => $request->input('description'),
            'status_updated_at' => now(),
        ]);

        return $this->success(new TrackingResource($tracking), 'Tracking update added.', 201);
    }

    public function media(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        Shipment::where('branch_id', $branchId)->findOrFail($id);

        $media = ShipmentMedia::where('shipment_id', $id)->get()->map(fn ($m) => [
            'id'          => $m->id,
            'shipment_id' => $m->shipment_id,
            'type'        => $m->type,
            'file_path'   => $m->file_path ? asset('storage/' . $m->file_path) : null,
            'stage'       => $m->stage,
            'caption'     => $m->caption,
            'uploaded_by' => $m->uploaded_by,
            'created_at'  => $m->created_at,
        ]);

        return $this->success($media);
    }

    public function uploadMedia(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        Shipment::where('branch_id', $branchId)->findOrFail($id);

        logger($request->all());

        $request->validate([
            'file'    => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:51200',
            'stage'   => 'nullable',
            'caption' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $type = in_array($file->getClientOriginalExtension(), ['mp4', 'mov']) ? 'video' : 'image';
        $path = $file->store("shipment-media/{$id}", 'public');

        $media = ShipmentMedia::create([
            'shipment_id' => $id,
            'type'        => $type,
            'file_path'   => $path,
            'stage'       => $request->input('stage', 'pickup'),
            'caption'     => $request->input('caption'),
            'uploaded_by' => auth()->id(),
        ]);

        return $this->success([
            'id'          => $media->id,
            'shipment_id' => $media->shipment_id,
            'type'        => $media->type,
            'file_path'   => asset('storage/' . $media->file_path),
            'stage'       => $media->stage,
            'caption'     => $media->caption,
        ], 'Media uploaded.', 201);
    }

    public function items(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        Shipment::where('branch_id', $branchId)->findOrFail($id);

        $receivers = Receiver::where('shipment_id', $id)
            ->with('items.product')
            ->get()
            ->map(fn ($r) => [
                'id'                  => $r->id,
                'receiver_name'       => $r->receiver_name,
                'receiver_phone'      => $r->receiver_phone,
                'receiver_email'      => $r->receiver_email,
                'country'             => $r->country,
                'state_region'        => $r->state_region,
                'city'                => $r->city,
                'address'             => $r->address,
                'receiver_id_type'    => $r->receiver_id_type,
                'receiver_id_number'  => $r->receiver_id_number,
                'items'               => $r->items->map(fn ($i) => [
                    'id'         => $i->id,
                    'box_no'     => $i->box_no,
                    'quantity'   => $i->quantity,
                    'item_cost'  => $i->item_cost,
                    'product'    => $i->product ? [
                        'id'   => $i->product->id,
                        'name' => $i->product->name,
                        'sku'  => $i->product->sku,
                    ] : null,
                ]),
            ]);

        return $this->success($receivers);
    }

    public function publicTrack(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['originBranch:id,name', 'destinationBranch:id,name'])
            ->first();

        if (!$shipment) {
            return $this->error('Shipment not found.', 404);
        }

        $trackings = Tracking::where('shipment_id', $shipment->id)
            ->orderBy('status_updated_at')
            ->get();

        return $this->success([
            'shipping_reference'       => $shipment->shipping_reference,
            'tracking_number'          => $shipment->tracking_number,
            'status'                   => $shipment->status instanceof \BackedEnum ? $shipment->status->value : $shipment->status,
            'estimated_delivery_date'  => $shipment->estimated_delivery_date,
            'delivered_at'             => $shipment->delivered_at,
            'origin_branch'            => $shipment->originBranch?->name,
            'destination_branch'       => $shipment->destinationBranch?->name,
            'tracking_history'         => TrackingResource::collection($trackings),
        ]);
    }

    private function formatShipment(Shipment $s): array
    {
        return [
            'id'                       => $s->id,
            'shipping_reference'       => $s->shipping_reference,
            'tracking_number'          => $s->tracking_number,
            'status'                   => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'payment_status'           => $s->payment_status,
            'shipping_cost'            => $s->shipping_cost,
            'total'                    => $s->total,
            'paid'                     => $s->paid,
            'total_ghs'                => $s->total_ghs,
            'exchange_rate_at_shipment'=> $s->exchange_rate_at_shipment,
            'container_number'         => $s->container_number,
            'shipped_at'               => $s->shipped_at,
            'estimated_delivery_date'  => $s->estimated_delivery_date,
            'delivered_at'             => $s->delivered_at,
            'created_at'               => $s->created_at,
            'client'                   => $s->client ? [
                'id'    => $s->client->id,
                'name'  => $s->client->name,
                'phone' => $s->client->phone,
            ] : null,
            'origin_branch_id'   => $s->origin_branch_id,
            'destination_branch_id' => $s->destination_branch_id,
            'origin_branch'      => $s->originBranch ? ['id' => $s->originBranch->id, 'name' => $s->originBranch->name] : null,
            'destination_branch' => $s->destinationBranch ? ['id' => $s->destinationBranch->id, 'name' => $s->destinationBranch->name] : null,
            'container_status'   => $s->containerStatus ? [
                'is_cleared' => (bool) $s->containerStatus->is_cleared,
                'review'     => $s->containerStatus->review,
            ] : null,
        ];
    }

    private function formatShipmentDetail(Shipment $s): array
    {
        $base = $this->formatShipment($s);

        $base['vat']                = $s->vat;
        $base['vat_percentage']     = $s->vat_percentage;
        $base['insurance']          = $s->insurance;
        $base['insurance_accepted'] = $s->insurance_accepted;
        $base['is_received']        = $s->is_received;
        $base['external_token']     = $s->external_token;
        $base['client_existence']   = $s->client_existence;

        if ($s->relationLoaded('receivers')) {
            $base['receivers'] = $s->receivers->map(fn ($r) => [
                'id'                  => $r->id,
                'receiver_name'       => $r->receiver_name,
                'receiver_phone'      => $r->receiver_phone,
                'receiver_email'      => $r->receiver_email,
                'country'             => $r->country,
                'state_region'        => $r->state_region,
                'city'                => $r->city,
                'address'             => $r->address,
                'receiver_id_type'    => $r->receiver_id_type,
                'receiver_id_number'  => $r->receiver_id_number,
                'items'               => $r->relationLoaded('items') ? $r->items->map(fn ($i) => [
                    'id'        => $i->id,
                    'box_no'    => $i->box_no,
                    'quantity'  => $i->quantity,
                    'item_cost' => $i->item_cost,
                    'product'   => $i->product ? ['id' => $i->product->id, 'name' => $i->product->name] : null,
                ]) : [],
            ]);
        }

        if ($s->relationLoaded('media')) {
            $base['media'] = $s->media->map(fn ($m) => [
                'id'        => $m->id,
                'type'      => $m->type,
                'file_path' => $m->file_path ? asset('storage/' . $m->file_path) : null,
                'stage'     => $m->stage,
                'caption'   => $m->caption,
            ]);
        }

        if ($s->relationLoaded('payments')) {
            $base['payments_total'] = $s->payments->sum('amount');
        }

        if ($s->relationLoaded('invoice')) {
            $base['invoice'] = $s->invoice ? [
                'id'           => $s->invoice->id,
                'total_amount' => $s->invoice->total_amount,
                'status'       => $s->invoice->status,
            ] : null;
        }

        return $base;
    }
}
