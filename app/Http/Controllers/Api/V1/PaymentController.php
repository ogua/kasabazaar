<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Models\Shipment;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_payment'), 403);

        $branchId  = $this->resolveBranch($request);
        $query     = Payment::where('branch_id', $branchId)
            ->with(['shipment:id,shipping_reference', 'enteredBy:id,name']);

        if ($shipmentId = $request->input('shipment_id')) {
            $query->where('shipment_id', $shipmentId);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('paid_on', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('paid_on', '<=', $to);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => new PaymentResource($p));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $payment = Payment::create(array_merge(
            $request->only([
                'shipment_id', 'payment_type', 'paying_type', 'description',
                'amount', 'currency', 'exchange_rate', 'amount_usd', 'amount_ghs',
                'paying_method', 'payment_note', 'cheque_no', 'bankname',
                'accountnumber', 'paid_on',
            ]),
            [
                'branch_id'   => $branchId,
                'user_id'     => auth()->id(),
                'payment_ref' => 'PAY-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'paid_on'     => $request->input('paid_on', now()),
            ]
        ));

        // Update shipment paid amount and payment_status
        if ($payment->shipment_id) {
            $shipment   = Shipment::find($payment->shipment_id);
            if ($shipment) {
                $totalPaid = Payment::where('shipment_id', $shipment->id)->sum('amount');
                $status    = $totalPaid >= $shipment->total ? 'paid'
                    : ($totalPaid > 0 ? 'partial' : 'pending');
                $shipment->update(['paid' => $totalPaid, 'payment_status' => $status]);
            }
        }

        return $this->success(new PaymentResource($payment->load(['shipment:id,shipping_reference', 'enteredBy:id,name'])), 'Payment recorded.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_payment'), 403);

        $branchId = $this->resolveBranch($request);
        $payment  = Payment::where('branch_id', $branchId)
            ->with(['shipment:id,shipping_reference', 'enteredBy:id,name'])
            ->findOrFail($id);

        return $this->success(new PaymentResource($payment));
    }


}
