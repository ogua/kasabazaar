<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShippingStatus;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Resources\TrackingResource;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentMedia;
use App\Models\Tracking;
use App\Services\LocationLookup;
use App\Services\PublicShipmentTracking;
use App\Services\ShipmentCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShipmentController extends BaseApiController
{
    /**
     * Payment-method codes the mobile app sends, mapped to the labels the admin
     * wizard stores. Labels already in that form pass through unchanged.
     */
    private const PAYING_METHODS = [
        'cash' => 'CASH',
        'bank_transfer' => 'BANK TRANSFER',
        'card' => 'CREDIT/DEBIT CARD',
        'cheque' => 'CHEQUE',
        'zelle' => 'Zelle',
        'cash_app' => 'Cash App',
        'paypal' => 'PAYPAL',
        'waived' => 'WAIVED',
    ];

    /**
     * One of ShipmentMedia::STAGES. Older app builds send free-text labels from
     * their own list; those are mapped onto the four stages the web library uses.
     */
    private static function mediaStage(?string $stage): string
    {
        $key = Str::slug((string) $stage);

        if (array_key_exists($key, ShipmentMedia::STAGES)) {
            return $key;
        }

        return match ($key) {
            'in-transit', 'at-warehouse', 'customs', 'loaded', 'shipped', 'port', 'sorting' => 'in-transit',
            'out-for-delivery', 'delivered', 'at-delivery' => 'delivery',
            'received', 'post-delivery' => 'post-delivery',
            default => 'pickup',
        };
    }

    /**
     * A receiver's country/region/city as the ids the wizard stores. The app may
     * send ids, or names that are matched to the lists; an unmatched name is kept.
     *
     * @param  array<string, mixed>  $receiverData
     * @return array{country: mixed, state_region: mixed, city: mixed}
     */
    private function receiverLocation(array $receiverData): array
    {
        $countryId = $receiverData['country_id']
            ?? LocationLookup::countryIdForName($receiverData['country'] ?? null)
            ?? LocationLookup::countryIdForPhone($receiverData['receiver_phone'] ?? null);
        $stateId = $receiverData['state_id'] ?? LocationLookup::stateIdForName($countryId, $receiverData['state_region'] ?? null);
        $cityId = $receiverData['city_id'] ?? LocationLookup::cityIdForName($stateId, $receiverData['city'] ?? null);

        return [
            'country' => $countryId ?? ($receiverData['country'] ?? null),
            'state_region' => $stateId ?? ($receiverData['state_region'] ?? null),
            'city' => $cityId ?? ($receiverData['city'] ?? null),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        // /logger('loadig shipments with branch: ' . $request->header('X-Branch-Id'));
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $query = Shipment::where('branch_id', $branchId)
            ->with(['client:id,name,phone', 'originBranch:id,name', 'destinationBranch:id,name', 'containerStatus:id,container_number,is_cleared,review']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipping_reference', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        // A group (awaiting / in_transit / completed — the groups the customer API and
        // client panel use), a single status, or a comma-separated list of statuses.
        if ($status = $request->input('status')) {
            $groups = [
                'awaiting' => ShippingStatus::awaitingDispatchValues(),
                'in_transit' => ShippingStatus::inTransitValues(),
                'completed' => ShippingStatus::completedValues(),
            ];

            $query->whereIn('status', $groups[$status] ?? explode(',', $status));
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginated = $query->latest()->paginate($perPage);

        return $this->paginated($paginated, fn ($s) => $this->formatShipment($s));
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $receivers = $request->input('receivers', []);

        // Item costs are line totals (unit value x quantity), as the wizard keeps them.
        $itemsSubtotal = collect($receivers)->flatMap(fn ($r) => $r['items'] ?? [])->sum(fn ($item) => (float) ($item['item_cost'] ?? 0));
        $subtotal = (float) ($request->input('shipping_cost') ?? $itemsSubtotal);
        $discount = (float) ($request->input('discount') ?? 0);
        $vatPercentage = (float) ($request->input('vat_percentage') ?? 0);
        $vat = round($subtotal * $vatPercentage / 100, 2);
        $insuranceAccepted = $request->boolean('insurance_accepted');
        $insurance = $insuranceAccepted ? (float) ($request->input('insurance') ?? 0) : 0.0;

        $shipment = DB::transaction(function () use ($request, $branchId, $receivers, $subtotal, $discount, $vatPercentage, $vat, $insuranceAccepted, $insurance) {
            $shipment = Shipment::create([
                'client_id' => $request->client_id,
                'branch_id' => $branchId,
                'origin_branch_id' => $request->origin_branch_id ?? $branchId,
                'destination_branch_id' => $request->destination_branch_id,
                ...ShipmentCreationService::referenceFields(
                    $request->input('shipment_type', 'new'),
                    $request->input('shipped_at'),
                    $request->input('existing_shipment_id'),
                ),
                'tracking_number' => Shipment::generateTrackingNumber(),
                'status' => $request->input('status') ?? 'pickup',
                'shipped_at' => $request->input('shipped_at'),
                'estimated_delivery_date' => $request->input('estimated_delivery_date'),
                'shipping_cost' => $subtotal,
                'discount' => $discount,
                'exchange_rate_at_shipment' => $request->input('exchange_rate_at_shipment'),
                'vat_percentage' => $vatPercentage,
                'vat' => $vat,
                'insurance_accepted' => $insuranceAccepted,
                'insurance' => $insurance,
                'total' => max(round($subtotal - $discount + $insurance + $vat, 2), 0),
                'paid' => 0,
                'recorded_by' => auth()->id(),
                'external_token' => Shipment::generateExternalToken(),
                'client_existence' => $request->input('client_existence')
                    ?? (Shipment::where('client_id', $request->client_id)->exists() ? 'returning-client' : 'new-client'),
                'client_note' => $request->input('client_note'),
                'description' => $request->input('description'),
            ]);

            foreach ($receivers as $receiverData) {
                $receiver = Receiver::create([
                    'shipment_id' => $shipment->id,
                    'receiver_name' => $receiverData['receiver_name'],
                    'receiver_phone' => $receiverData['receiver_phone'] ?? null,
                    'receiver_email' => $receiverData['receiver_email'] ?? null,
                    ...$this->receiverLocation($receiverData),
                    'address' => $receiverData['address'] ?? null,
                    'receiver_id_type' => $receiverData['receiver_id_type'] ?? null,
                    'receiver_id_number' => $receiverData['receiver_id_number'] ?? null,
                    'whatsapp_opt_in_at' => ! empty($receiverData['whatsapp_opt_in']) ? now() : null,
                ]);

                foreach ($receiverData['items'] ?? [] as $item) {
                    $isVehicle = (bool) ($item['is_vehicle'] ?? false);

                    ShipmentItem::create([
                        'shipment_id' => $shipment->id,
                        'receiver_id' => $receiver->id,
                        'product_id' => $item['product_id'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'item_cost' => $item['item_cost'] ?? 0,
                        'box_no' => $item['box_no'] ?? null,
                        'is_vehicle' => $isVehicle,
                        'vin' => $isVehicle ? ($item['vin'] ?? null) : null,
                        'vehicle_make' => $isVehicle ? ($item['vehicle_make'] ?? null) : null,
                        'vehicle_model' => $isVehicle ? ($item['vehicle_model'] ?? null) : null,
                        'vehicle_year' => $isVehicle ? ($item['vehicle_year'] ?? null) : null,
                    ]);
                }
            }

            foreach ($request->input('payments', []) as $paymentData) {
                $amountUsd = (float) $paymentData['amount_usd'];
                $rate = (float) ($paymentData['exchange_rate'] ?? $request->input('exchange_rate_at_shipment') ?? 0);

                $shipment->payments()->create([
                    'branch_id' => $shipment->branch_id,
                    'user_id' => auth()->id(),
                    'currency' => 'USD',
                    'paying_method' => self::PAYING_METHODS[strtolower($paymentData['paying_method'])] ?? $paymentData['paying_method'],
                    'amount' => $amountUsd,
                    'amount_usd' => $amountUsd,
                    'exchange_rate' => $rate ?: null,
                    'amount_ghs' => $paymentData['amount_ghs'] ?? ($rate ? round($amountUsd * $rate, 2) : null),
                    'paid_on' => $paymentData['paid_on'] ?? now(),
                    'payment_note' => $paymentData['payment_note'] ?? null,
                    'bankname' => $paymentData['bankname'] ?? null,
                    'accountnumber' => $paymentData['accountnumber'] ?? null,
                    'cheque_no' => $paymentData['cheque_no'] ?? null,
                    'payment_ref' => 'PAY-'.strtoupper(bin2hex(random_bytes(4))),
                    'payment_type' => 'credit',
                    'change' => 0,
                ]);
            }

            app(ShipmentCreationService::class)->finalise($shipment);

            return $shipment;
        });

        $shipment->load(['client', 'originBranch', 'destinationBranch', 'receivers.items.product']);

        return $this->success($this->formatShipmentDetail($shipment), 'Shipment created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $shipment = Shipment::whereIn('branch_id', $this->userBranchIds())
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
        $shipment = Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $data = $request->only([
            'status', 'destination_branch_id',
            'estimated_delivery_date', 'shipping_cost', 'discount',
            'vat_percentage', 'insurance_accepted', 'insurance',
            'is_received', 'delivered_at', 'description', 'client_note',
            'shipped_at', 'exchange_rate_at_shipment',
        ]);

        $notNullableFields = ['shipping_cost', 'discount', 'insurance', 'vat_percentage', 'insurance_accepted'];
        foreach ($notNullableFields as $field) {
            if (array_key_exists($field, $data) && is_null($data[$field])) {
                unset($data[$field]);
            }
        }

        // Money is recalculated the wizard's way whenever any part of it changes;
        // the total and paid figures are never taken from the client.
        if (array_intersect(array_keys($data), ['shipping_cost', 'discount', 'vat_percentage', 'insurance_accepted', 'insurance'])) {
            $subtotal = (float) ($data['shipping_cost'] ?? $shipment->shipping_cost);
            $discount = (float) ($data['discount'] ?? $shipment->discount);
            $vatPercentage = (float) ($data['vat_percentage'] ?? $shipment->vat_percentage);
            $insuranceAccepted = (bool) ($data['insurance_accepted'] ?? $shipment->insurance_accepted);
            $insurance = $insuranceAccepted ? (float) ($data['insurance'] ?? $shipment->insurance) : 0.0;
            $vat = round($subtotal * $vatPercentage / 100, 2);

            $data = [...$data, 'insurance' => $insurance, 'vat' => $vat, 'total' => max(round($subtotal - $discount + $insurance + $vat, 2), 0)];
        }

        $shipment->update($data);

        if (array_key_exists('total', $data)) {
            $shipment->recalculatePaymentStatus();
        }

        return $this->success($this->formatShipmentDetail($shipment->fresh(['client', 'originBranch', 'destinationBranch'])));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_shipment'), 403);

        $shipment = Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);
        $shipment->delete();

        return $this->success(null, 'Shipment deleted.');
    }

    public function trackings(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $shipment = Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $trackings = Tracking::where('shipment_id', $id)
            ->orderBy('status_updated_at', 'asc')
            ->get();

        return $this->success(TrackingResource::collection($trackings));
    }

    public function addTracking(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $shipment = Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $request->validate([
            'status' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $tracking = Tracking::create([
            'shipment_id' => $id,
            'branch_id' => $branchId,
            'status' => $request->status,
            'description' => $request->input('description'),
            'status_updated_at' => now(),
        ]);

        return $this->success(new TrackingResource($tracking), 'Tracking update added.', 201);
    }

    public function media(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $media = ShipmentMedia::where('shipment_id', $id)->get()->map(fn ($m) => [
            'id' => $m->id,
            'shipment_id' => $m->shipment_id,
            'type' => $m->type,
            'file_path' => $m->file_path ? asset('storage/'.$m->file_path) : null,
            'stage' => $m->stage,
            'stage_label' => $m->stageLabel(),
            'caption' => $m->caption,
            'uploaded_by' => $m->uploaded_by,
            // update_shipment may remove anything; others only their own uploads.
            'can_remove' => $m->isRemovableBy($request->user()),
            'created_at' => $m->created_at,
        ]);

        return $this->success($media);
    }

    /**
     * The branch's Media & Evidence library (the admin page of that name): every
     * shipment's photos, videos and documents, newest first, with the client and
     * container each belongs to so the app can group them into albums.
     */
    public function mediaLibrary(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('view_any_shipment'), 403);

        $request->validate([
            'search' => 'nullable|string|max:100',
            'container' => 'nullable|integer',
            'client_id' => 'nullable|uuid',
            'shipment_id' => 'nullable|uuid',
            'stage' => ['nullable', Rule::in(array_keys(ShipmentMedia::STAGES))],
            'type' => 'nullable|in:image,video,document',
            'from' => 'nullable|date',
            'until' => 'nullable|date',
        ]);

        $branchId = $this->resolveBranch($request);
        $search = trim((string) $request->input('search'));

        $paginated = ShipmentMedia::query()
            ->whereHas('shipment', fn ($query) => $query->where('branch_id', $branchId))
            ->with(['shipment:id,client_id,container_number,shipping_reference,tracking_number', 'shipment.client:id,name', 'uploader:id,name'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('caption', 'like', "%{$search}%")
                ->orWhereHas('shipment', fn ($shipment) => $shipment
                    ->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('shipping_reference', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%")))))
            ->when($request->input('container'), fn ($query, $container) => $query->whereHas('shipment', fn ($q) => $q->where('container_number', $container)))
            ->when($request->input('client_id'), fn ($query, $clientId) => $query->whereHas('shipment', fn ($q) => $q->where('client_id', $clientId)))
            ->when($request->input('shipment_id'), fn ($query, $shipmentId) => $query->where('shipment_id', $shipmentId))
            ->when($request->input('stage'), fn ($query, $stage) => $query->where('stage', $stage))
            ->when($request->input('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->input('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->input('until'), fn ($query, $until) => $query->whereDate('created_at', '<=', $until))
            ->latest()
            ->paginate((int) $request->input('per_page', 40));

        return $this->paginated($paginated, fn (ShipmentMedia $media) => [
            'id' => $media->id,
            'type' => $media->type,
            'url' => $media->url(),
            'file_name' => $media->fileName(),
            'stage' => $media->stage,
            'stage_label' => $media->stageLabel(),
            'caption' => $media->caption,
            'uploaded_by' => $media->uploader?->name,
            'can_remove' => $media->isRemovableBy($request->user()),
            'created_at' => $media->created_at,
            'shipment' => [
                'id' => $media->shipment?->id,
                'shipping_reference' => $media->shipment?->shipping_reference,
                'tracking_number' => $media->shipment?->tracking_number,
                'container_number' => $media->shipment?->container_number,
                'client' => $media->shipment?->client ? ['id' => $media->shipment->client->id, 'name' => $media->shipment->client->name] : null,
            ],
        ]);
    }

    public function uploadMedia(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,heic,mp4,mov,m4v,3gp,webm,pdf,doc,docx,xls,xlsx,csv,txt|max:51200',
            'stage' => 'nullable|string|max:50',
            'caption' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $path = $file->store("shipment-media/{$id}", 'public');

        $media = ShipmentMedia::create([
            'shipment_id' => $id,
            'type' => ShipmentMedia::typeForPath($file->getClientOriginalName()),
            'file_path' => $path,
            'stage' => self::mediaStage($request->input('stage')),
            'caption' => $request->input('caption'),
            'uploaded_by' => auth()->id(),
        ]);

        return $this->success([
            'id' => $media->id,
            'shipment_id' => $media->shipment_id,
            'type' => $media->type,
            'file_path' => asset('storage/'.$media->file_path),
            'stage' => $media->stage,
            'caption' => $media->caption,
        ], 'Media uploaded.', 201);
    }

    /**
     * Remove a photo, video or document uploaded by mistake, along with its stored
     * file. Staff who can update shipments may remove any media; others only their own.
     */
    public function deleteMedia(string $id, string $mediaId): JsonResponse
    {
        Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $media = ShipmentMedia::where('shipment_id', $id)->findOrFail($mediaId);

        abort_unless(
            auth()->user()->can('update_shipment') || $media->uploaded_by === auth()->id(),
            403
        );

        $media->delete();

        return $this->success(null, 'Media deleted.');
    }

    /**
     * Record an agent-based delivery for a shipment — a parallel concept to
     * the fleet Trip/TripShipment delivery flow (see ShipmentDelivery model),
     * for agents who hand-deliver items outside the vehicle/driver system.
     */
    public function recordDelivery(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_shipment'), 403);

        $shipment = Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $request->validate([
            'clearing_agent_id' => 'required|uuid|exists:clearing_agents,id',
            'delivered_at' => 'nullable|date',
            'delivery_notes' => 'nullable|string',
            'receiver_signature' => 'nullable|file|image|max:5120',
            'is_received' => 'nullable|boolean',
        ]);

        $signaturePath = null;
        if ($request->hasFile('receiver_signature')) {
            $signaturePath = $request->file('receiver_signature')->store("shipment-delivery-signatures/{$id}", 'public');
        }

        $deliveredAt = $request->input('delivered_at', now());

        $delivery = \App\Models\ShipmentDelivery::create([
            'shipment_id' => $shipment->id,
            'clearing_agent_id' => $request->input('clearing_agent_id'),
            'delivered_at' => $deliveredAt,
            'delivery_notes' => $request->input('delivery_notes'),
            'receiver_signature' => $signaturePath,
            'recorded_by' => auth()->id(),
        ]);

        // Use the model's own save() (not a relation-scoped update()) so
        // ShipmentObserver's status:delivered notification still fires.
        $shipment->status = 'delivered';
        $shipment->delivered_at = $deliveredAt;
        // A receiver signature is proof of receipt in itself.
        if ($request->boolean('is_received') || $signaturePath) {
            $shipment->is_received = true;
        }
        $agent = $delivery->clearingAgent?->name;
        $shipment->statusHistoryNote = [
            'remarks' => trim(($agent ? "Delivered by {$agent}. " : 'Delivered. ').$request->input('delivery_notes', '')),
        ];
        $shipment->save();

        return $this->success([
            'id' => $delivery->id,
            'shipment_id' => $delivery->shipment_id,
            'clearing_agent_id' => $delivery->clearing_agent_id,
            'delivered_at' => $delivery->delivered_at,
            'delivery_notes' => $delivery->delivery_notes,
            'receiver_signature' => $delivery->receiver_signature ? asset('storage/'.$delivery->receiver_signature) : null,
        ], 'Delivery recorded.', 201);
    }

    public function items(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        Shipment::whereIn('branch_id', $this->userBranchIds())->findOrFail($id);

        $receivers = Receiver::where('shipment_id', $id)
            ->with('items.product')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'receiver_name' => $r->receiver_name,
                'receiver_phone' => $r->receiver_phone,
                'receiver_email' => $r->receiver_email,
                'country' => $r->country,
                'state_region' => $r->state_region,
                'city' => $r->city,
                'address' => $r->address,
                'receiver_id_type' => $r->receiver_id_type,
                'receiver_id_number' => $r->receiver_id_number,
                'items' => $r->items->map(fn ($i) => [
                    'id' => $i->id,
                    'box_no' => $i->box_no,
                    'quantity' => $i->quantity,
                    'item_cost' => $i->item_cost,
                    'product' => $i->product ? [
                        'id' => $i->product->id,
                        'name' => $i->product->name,
                        'sku' => $i->product->sku,
                    ] : null,
                ]),
            ]);

        return $this->success($receivers);
    }

    /**
     * Public tracking by tracking number or shipping reference, with the same content
     * and privacy rules as the kasarose.com tracking page (PublicShipmentTracking).
     * The original keys (origin_branch, destination_branch, tracking_history) are kept
     * for app builds released before the nine-stage tracker.
     */
    public function publicTrack(string $trackingNumber, PublicShipmentTracking $tracking): JsonResponse
    {
        $shipment = $tracking->find($trackingNumber);

        if (! $shipment) {
            return $this->error('Shipment not found.', 404);
        }

        return $this->success($this->trackingPayload($shipment, $tracking, $tracking->lookedUpByTrackingNumber($shipment, $trackingNumber)));
    }

    /**
     * The same payload for the /my-shipment/{token} and /shipment-label/{token} links in
     * alerts and on printed labels, so the app can open them directly.
     */
    public function publicView(string $token, PublicShipmentTracking $tracking): JsonResponse
    {
        $shipment = Shipment::query()
            ->with(['originBranch:id,name,country', 'destinationBranch:id,name,country'])
            ->where('public_view_token', $token)
            ->first();

        if (! $shipment) {
            return $this->error('Shipment not found.', 404);
        }

        return $this->success($this->trackingPayload($shipment, $tracking, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function trackingPayload(Shipment $shipment, PublicShipmentTracking $tracking, bool $byTrackingNumber): array
    {
        $trackings = Tracking::where('shipment_id', $shipment->id)
            ->orderBy('status_updated_at')
            ->get();

        return [
            ...$tracking->payload($shipment, $byTrackingNumber),
            'origin_branch' => $tracking->placeName($shipment, 'origin'),
            'destination_branch' => $tracking->placeName($shipment, 'destination'),
            'tracking_history' => TrackingResource::collection($trackings)->resolve(),
        ];
    }

    private function formatShipment(Shipment $s): array
    {
        return [
            'id' => $s->id,
            'shipping_reference' => $s->shipping_reference,
            'tracking_number' => $s->tracking_number,
            'status' => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'payment_status' => $s->payment_status,
            'shipping_cost' => $s->shipping_cost,
            'total' => $s->total,
            'paid' => $s->paid,
            'total_ghs' => $s->total_ghs,
            'exchange_rate_at_shipment' => $s->exchange_rate_at_shipment,
            'container_number' => $s->container_number,
            'shipped_at' => $s->shipped_at,
            'estimated_delivery_date' => $s->estimated_delivery_date,
            'delivered_at' => $s->delivered_at,
            'description' => $s->description,
            'public_view_token' => $s->public_view_token,
            'created_at' => $s->created_at,
            'client' => $s->client ? [
                'id' => $s->client->id,
                'name' => $s->client->name,
                'phone' => $s->client->phone,
            ] : null,
            'origin_branch_id' => $s->origin_branch_id,
            'destination_branch_id' => $s->destination_branch_id,
            'origin_branch' => $s->originBranch ? ['id' => $s->originBranch->id, 'name' => $s->originBranch->name] : null,
            'destination_branch' => $s->destinationBranch ? ['id' => $s->destinationBranch->id, 'name' => $s->destinationBranch->name] : null,
            'container_status' => $s->containerStatus ? [
                'is_cleared' => (bool) $s->containerStatus->is_cleared,
                'review' => $s->containerStatus->review,
            ] : null,
        ];
    }

    private function formatShipmentDetail(Shipment $s): array
    {
        $base = $this->formatShipment($s);

        $base['vat'] = $s->vat;
        $base['vat_percentage'] = $s->vat_percentage;
        $base['insurance'] = $s->insurance;
        $base['insurance_accepted'] = $s->insurance_accepted;
        $base['is_received'] = $s->is_received;
        $base['external_token'] = $s->external_token;
        $base['client_existence'] = $s->client_existence;

        if ($s->relationLoaded('receivers')) {
            $base['receivers'] = $s->receivers->map(fn ($r) => [
                'id' => $r->id,
                'receiver_name' => $r->receiver_name,
                'receiver_phone' => $r->receiver_phone,
                'receiver_email' => $r->receiver_email,
                'country' => $r->country,
                'state_region' => $r->state_region,
                'city' => $r->city,
                'address' => $r->address,
                'receiver_id_type' => $r->receiver_id_type,
                'receiver_id_number' => $r->receiver_id_number,
                'items' => $r->relationLoaded('items') ? $r->items->map(fn ($i) => [
                    'id' => $i->id,
                    'box_no' => $i->box_no,
                    'quantity' => $i->quantity,
                    'item_cost' => $i->item_cost,
                    'product' => $i->product ? ['id' => $i->product->id, 'name' => $i->product->name] : null,
                ]) : [],
            ]);
        }

        if ($s->relationLoaded('media')) {
            $base['media'] = $s->media->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'file_path' => $m->file_path ? asset('storage/'.$m->file_path) : null,
                'stage' => $m->stage,
                'caption' => $m->caption,
            ]);
        }

        if ($s->relationLoaded('payments')) {
            $base['payments_total'] = $s->payments->sum('amount');
        }

        if ($s->relationLoaded('invoice')) {
            $base['invoice'] = $s->invoice ? [
                'id' => $s->invoice->id,
                'total_amount' => $s->invoice->total_amount,
                'status' => $s->invoice->status,
            ] : null;
        }

        return $base;
    }
}
