<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\ShippingStatus;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Services\PublicShipmentTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerShipmentController extends CustomerBaseController
{
    /**
     * Status groups the app shows as tabs, matching the client panel's
     * Pending / Transit / Delivered pages.
     *
     * @return array<string, list<string>>
     */
    private static function statusGroups(): array
    {
        return [
            'awaiting' => ShippingStatus::awaitingDispatchValues(),
            'in_transit' => ShippingStatus::inTransitValues(),
            'completed' => ShippingStatus::completedValues(),
            'cancelled' => [ShippingStatus::cancelled->value],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Shipment::where('client_id', $this->clientId())
            ->with(['originBranch:id,name,country', 'destinationBranch:id,name,country'])
            ->latest();

        $this->filterByStatus($query, $request->input('status'));

        $paginated = $query->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($s) => $this->formatShipment($s));
    }

    /**
     * The client's home screen: counts per status group and payment state, the
     * outstanding balance, and the shipments still on their way.
     */
    public function dashboard(): JsonResponse
    {
        $shipments = Shipment::where('client_id', $this->clientId());

        $statusCounts = (clone $shipments)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $countFor = fn (array $statuses): int => (int) collect($statuses)->sum(fn ($status) => $statusCounts[$status] ?? 0);

        $active = (clone $shipments)
            ->whereIn('status', [...ShippingStatus::awaitingDispatchValues(), ...ShippingStatus::inTransitValues()])
            ->with(['originBranch:id,name,country', 'destinationBranch:id,name,country'])
            ->latest()
            ->limit(10)
            ->get();

        $unpaid = (clone $shipments)->where('status', '!=', ShippingStatus::cancelled->value)->get(['id', 'total', 'paid']);

        return $this->success([
            'counts' => [
                'total' => (int) $statusCounts->sum(),
                ...collect(self::statusGroups())->map($countFor)->all(),
            ],
            'payments' => [
                'pending' => (clone $shipments)->where('payment_status', 'pending')->count(),
                'partial' => (clone $shipments)->where('payment_status', 'partial')->count(),
                'paid' => (clone $shipments)->where('payment_status', 'paid')->count(),
                'outstanding_usd' => round((float) $unpaid->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid)), 2),
            ],
            'active_shipments' => $active->map(fn ($s) => $this->formatShipment($s))->values(),
        ]);
    }

    public function show(string $id, PublicShipmentTracking $tracking): JsonResponse
    {
        $shipment = Shipment::where('client_id', $this->clientId())
            ->with([
                'receivers.items.product', 'receivers.mcountry:id,name', 'receivers.mstate:id,name', 'receivers.mcity:id,name',
                'media', 'invoice', 'payments', 'originBranch:id,name,country', 'destinationBranch:id,name,country',
            ])
            ->findOrFail($id);

        return $this->success($this->formatShipmentDetail($shipment, $tracking));
    }

    private function filterByStatus(Builder $query, ?string $status): void
    {
        if (blank($status)) {
            return;
        }

        $groups = self::statusGroups();

        if (isset($groups[$status])) {
            $query->whereIn('status', $groups[$status]);

            return;
        }

        $query->whereIn('status', explode(',', $status));
    }

    private function formatShipment(Shipment $s): array
    {
        $rawStatus = (string) $s->getRawOriginal('status');
        $meta = PublicShipmentTracking::statusMeta($rawStatus, (bool) $s->is_received);

        return [
            'id' => $s->id,
            'shipping_reference' => $s->shipping_reference,
            'tracking_number' => $s->tracking_number,
            'status' => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'status_label' => $meta['label'],
            'status_meta' => ['label' => $meta['label'], 'step' => $meta['step'], 'tone' => $meta['class'], 'icon' => $meta['icon']],
            'payment_status' => $s->payment_status,
            'total' => $s->total,
            'paid' => $s->paid,
            'balance' => round(max(0, (float) $s->total - (float) $s->paid), 2),
            'total_ghs' => $s->total_ghs,
            'estimated_delivery_date' => $s->estimated_delivery_date,
            'shipped_at' => $s->shipped_at,
            'delivered_at' => $s->delivered_at,
            'origin_branch' => $s->originBranch ? ['id' => $s->originBranch->id, 'name' => $s->originBranch->name, 'country' => $s->originBranch->country] : null,
            'destination_branch' => $s->destinationBranch ? ['id' => $s->destinationBranch->id, 'name' => $s->destinationBranch->name, 'country' => $s->destinationBranch->country] : null,
            'can_rate' => in_array($rawStatus, ShippingStatus::completedValues(), true),
            'created_at' => $s->created_at,
        ];
    }

    private function formatShipmentDetail(Shipment $s, PublicShipmentTracking $tracking): array
    {
        $base = $this->formatShipment($s);
        $public = $tracking->payload($s, true);

        $base['costs'] = [
            'shipping_cost' => (float) $s->shipping_cost,
            'discount' => (float) $s->discount,
            'vat_percentage' => (float) $s->vat_percentage,
            'vat' => (float) $s->vat,
            'insurance' => (float) $s->insurance,
            'total' => (float) $s->total,
            'paid' => (float) $s->paid,
            'balance' => $base['balance'],
        ];

        $base['msc_tracking_number'] = $s->msc_tracking_number;
        $base['msc_tracking_url'] = $s->mscTrackingUrl();
        $base['stages'] = $public['stages'];
        $base['history'] = $public['history'];
        $base['web_tracking_url'] = $public['web_url'];
        $base['invoice_url'] = route('shipping-invoice-download', $s->id);

        $base['receivers'] = $s->receivers->map(fn (Receiver $r) => [
            'id' => $r->id,
            'receiver_name' => $r->receiver_name,
            'receiver_phone' => $r->receiver_phone,
            'receiver_email' => $r->receiver_email,
            'country' => $r->mcountry?->name ?? (is_numeric($r->country) ? null : $r->country),
            'state_region' => $r->mstate?->name ?? (is_numeric($r->state_region) ? null : $r->state_region),
            'city' => $r->mcity?->name ?? (is_numeric($r->city) ? null : $r->city),
            'address' => $r->address,
            'items' => $r->items->map(fn ($i) => [
                'id' => $i->id,
                'quantity' => $i->quantity,
                'box_no' => $i->box_no,
                'item_cost' => (float) $i->item_cost,
                'is_vehicle' => (bool) $i->is_vehicle,
                'vehicle' => $i->is_vehicle ? trim(implode(' ', array_filter([$i->vehicle_year, $i->vehicle_make, $i->vehicle_model]))) : null,
                'product' => $i->product ? [
                    'id' => $i->product->id,
                    'name' => $i->product->name,
                    'image_url' => $i->product->product_image ? asset('storage/'.$i->product->product_image) : null,
                ] : null,
            ])->values(),
        ])->values();

        $base['media'] = $s->media->map(fn ($m) => [
            'id' => $m->id,
            'media_type' => $m->type,
            'file_path' => $m->file_path,
            'url' => $m->url(),
            'stage' => $m->stage,
            'stage_label' => $m->stageLabel(),
            'caption' => $m->caption,
        ])->values();

        $base['invoice'] = $s->invoice ? [
            'id' => $s->invoice->id,
            'total_amount' => $s->invoice->total_amount,
            'status' => $s->invoice->status,
        ] : null;

        $base['payments_total'] = $s->payments->sum('amount_usd');
        $base['payments'] = $s->payments->sortByDesc('paid_on')->map(fn ($p) => [
            'id' => $p->id,
            'amount_usd' => (float) $p->amount_usd,
            'amount_ghs' => (float) $p->amount_ghs,
            'paying_method' => $p->paying_method,
            'payment_reference' => $p->payment_ref,
            'paid_on' => $p->paid_on,
            'receipt_url' => route('payment-receipt', $p->id),
        ])->values();

        return $base;
    }
}
