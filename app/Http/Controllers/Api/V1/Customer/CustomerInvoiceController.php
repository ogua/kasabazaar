<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerInvoiceController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $paginated = Invoice::whereHas('shipment', fn ($q) => $q->where('client_id', $this->clientId()))
            ->with(['shipment:id,shipping_reference,status,total'])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($i) => $this->formatInvoice($i));
    }

    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::whereHas('shipment', fn ($q) => $q->where('client_id', $this->clientId()))
            ->with(['shipment:id,shipping_reference,status,total,total_ghs,paid,payment_status'])
            ->findOrFail($id);

        return $this->success($this->formatInvoice($invoice));
    }

    private function formatInvoice(Invoice $i): array
    {
        return [
            'id'           => $i->id,
            'shipment_id'  => $i->shipment_id,
            'total_amount' => $i->total_amount,
            'status'       => $i->status,
            'created_at'   => $i->created_at,
            'shipment'     => $i->shipment ? [
                'id'                 => $i->shipment->id,
                'shipping_reference' => $i->shipment->shipping_reference,
                'status'             => $i->shipment->status instanceof \BackedEnum ? $i->shipment->status->value : $i->shipment->status,
                'total'              => $i->shipment->total,
                'total_ghs'          => $i->shipment->total_ghs,
                'paid'               => $i->shipment->paid,
                'payment_status'     => $i->shipment->payment_status,
            ] : null,
        ];
    }
}
