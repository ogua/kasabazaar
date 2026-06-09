<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerShipmentController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Shipment::where('client_id', $this->clientId())
            ->with(['originBranch:id,name', 'destinationBranch:id,name'])
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($s) => $this->formatShipment($s));
    }

    public function show(string $id): JsonResponse
    {
        $shipment = Shipment::where('client_id', $this->clientId())
            ->with(['receivers.items.product', 'media', 'invoice', 'payments', 'originBranch:id,name', 'destinationBranch:id,name'])
            ->findOrFail($id);

        return $this->success($this->formatShipmentDetail($shipment));
    }

    private function formatShipment(Shipment $s): array
    {
        return [
            'id'                     => $s->id,
            'shipping_reference'     => $s->shipping_reference,
            'tracking_number'        => $s->tracking_number,
            'status'                 => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'payment_status'         => $s->payment_status,
            'total'                  => $s->total,
            'paid'                   => $s->paid,
            'total_ghs'              => $s->total_ghs,
            'estimated_delivery_date'=> $s->estimated_delivery_date,
            'shipped_at'             => $s->shipped_at,
            'delivered_at'           => $s->delivered_at,
            'origin_branch'          => $s->originBranch ? ['id' => $s->originBranch->id, 'name' => $s->originBranch->name] : null,
            'destination_branch'     => $s->destinationBranch ? ['id' => $s->destinationBranch->id, 'name' => $s->destinationBranch->name] : null,
            'created_at'             => $s->created_at,
        ];
    }

    private function formatShipmentDetail(Shipment $s): array
    {
        $base = $this->formatShipment($s);

        $base['receivers']      = $s->receivers->map(fn ($r) => [
            'id'             => $r->id,
            'receiver_name'  => $r->receiver_name,
            'receiver_phone' => $r->receiver_phone,
            'address'        => $r->address,
            'items'          => $r->items->map(fn ($i) => [
                'id'       => $i->id,
                'quantity' => $i->quantity,
                'box_no'   => $i->box_no,
                'product'  => $i->product ? ['id' => $i->product->id, 'name' => $i->product->name] : null,
            ])->values(),
        ])->values();

        $base['media'] = $s->media->map(fn ($m) => [
            'id'         => $m->id,
            'media_type' => $m->type,
            'file_path'  => $m->file_path,
            'stage'      => $m->stage,
            'caption'    => $m->caption,
        ])->values();

        $base['invoice'] = $s->invoice ? [
            'id'           => $s->invoice->id,
            'total_amount' => $s->invoice->total_amount,
            'status'       => $s->invoice->status,
        ] : null;

        $base['payments_total'] = $s->payments->sum('amount_usd');

        return $base;
    }
}
