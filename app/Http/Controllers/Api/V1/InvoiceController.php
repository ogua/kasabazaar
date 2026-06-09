<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use App\Models\Shipment;
use App\Http\Resources\InvoiceResource;
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

        return $this->paginated($paginated, fn ($i) => new InvoiceResource($i));
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

        return $this->success(new InvoiceResource($invoice));
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

        return $this->success(new InvoiceResource($invoice->fresh('shipment')));
    }


}
