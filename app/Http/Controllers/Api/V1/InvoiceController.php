<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use App\Models\Shipment;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_invoice'), 403);

        $branchId = $this->resolveBranch($request);

        $query = Invoice::whereHas('shipment', fn ($q) => $q->where('branch_id', $branchId))
            ->with([
                'shipment:id,shipping_reference,client_id,branch_id,total,paid,payment_status,destination_branch_id,shipped_at',
                'shipment.client:id,name,phone',
                'shipment.destinationBranch:id,name',
            ]);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($i) => $this->formatInvoice($i));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_invoice'), 403);

        $branchId = $this->resolveBranch($request);
        $invoice  = Invoice::whereHas('shipment', fn ($q) => $q->where('branch_id', $branchId))
            ->with([
                'shipment.client',
                'shipment.originBranch:id,name',
                'shipment.destinationBranch:id,name',
                'shipment.payments',
                'shipment.items.product:id,name',
                'shipment.receivers',
            ])
            ->findOrFail($id);

        return $this->success($this->formatInvoice($invoice, detailed: true));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_invoice'), 403);

        $branchId = $this->resolveBranch($request);
        $invoice  = Invoice::whereHas('shipment', fn ($q) => $q->where('branch_id', $branchId))
            ->findOrFail($id);

        $request->validate([
            'status'       => 'sometimes|in:unpaid,paid,partial',
            'total_amount' => 'sometimes|numeric|min:0',
        ]);

        $invoice->update($request->only(['status', 'total_amount']));

        return $this->success($this->formatInvoice($invoice->fresh('shipment')));
    }

    private function formatInvoice(Invoice $i, bool $detailed = false): array
    {
        $s = $i->shipment;

        $base = [
            'id'           => $i->id,
            'shipment_id'  => $i->shipment_id,
            'total_amount' => $i->total_amount,
            'status'       => (function() use ($i) {
                $s = $i->status instanceof \BackedEnum ? $i->status->value : $i->status;
                return $s === 'pending' ? 'unpaid' : $s; // normalize legacy 'pending' → 'unpaid'
            })(),
            'created_at'   => $i->created_at,
            'shipment'     => $s ? [
                'id'                 => $s->id,
                'shipping_reference' => $s->shipping_reference,
                'status'             => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
                'payment_status'     => $s->payment_status,
                'total'              => $s->total,
                'paid'               => $s->paid,
                'shipped_at'         => $s->shipped_at,
                'destination_branch' => $s->destinationBranch?->name,
                'client'             => $s->client ? [
                    'id'    => $s->client->id,
                    'name'  => $s->client->name,
                    'phone' => $s->client->phone,
                    'email' => $s->client->email,
                ] : null,
            ] : null,
        ];

        if (!$detailed || !$s) {
            return $base;
        }

        // Enrich shipment with full financial + logistic data
        $base['shipment'] = array_merge($base['shipment'], [
            'shipping_cost'           => $s->shipping_cost,
            'vat'                     => $s->vat ?? 0,
            'vat_percentage'          => $s->vat_percentage ?? 0,
            'insurance'               => $s->insurance ?? 0,
            'shipped_at'              => $s->shipped_at,
            'estimated_delivery_date' => $s->estimated_delivery_date,
            'delivered_at'            => $s->delivered_at,
            'origin_branch'           => $s->originBranch?->name,
            'destination_branch'      => $s->destinationBranch?->name,

            'payments' => $s->payments->map(fn ($p) => [
                'id'             => $p->id,
                'amount'         => $p->amount,
                'paying_method'  => $p->paying_method,
                'paying_type'    => $p->paying_type,
                'payment_ref'    => $p->payment_ref,
                'payment_note'   => $p->payment_note,
                'paid_on'        => $p->paid_on,
            ])->values(),

            'items' => $s->items->map(fn ($item) => [
                'id'          => $item->id,
                'box_no'      => $item->box_no,
                'product'     => $item->product?->name,
                'quantity'    => $item->quantity,
                'item_cost'   => $item->item_cost,
                'subtotal'    => $item->quantity * $item->item_cost,
            ])->values(),

            'receivers' => $s->receivers->map(fn ($r) => [
                'id'             => $r->id,
                'receiver_name'  => $r->receiver_name,
                'receiver_phone' => $r->receiver_phone,
                'city'           => $r->city,
                'country'        => $r->country,
            ])->values(),
        ]);

        return $base;
    }
}
