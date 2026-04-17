<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Models\Shipment;
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
            ->with(['shipment:id,shipping_reference', 'enteredby:id,name']);

        if ($from = $request->input('date_from')) {
            $query->whereDate('paid_on', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('paid_on', '<=', $to);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => $this->formatPayment($p));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_payment'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'shipment_id'   => 'nullable|uuid|exists:shipments,id',
            'payment_type'  => 'nullable|in:debit,credit',
            'paying_type'   => 'nullable|string|max:100',
            'description'   => 'nullable|string',
            'amount'        => 'required|numeric|min:0',
            'currency'      => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'amount_usd'    => 'nullable|numeric|min:0',
            'amount_ghs'    => 'nullable|numeric|min:0',
            'paying_method' => 'nullable|in:cash,bank_transfer,mobile_money,cheque,card,other',
            'payment_note'  => 'nullable|string',
            'cheque_no'     => 'nullable|string',
            'bankname'      => 'nullable|string',
            'accountnumber' => 'nullable|string',
            'paid_on'       => 'nullable|date',
        ]);

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

        return $this->success($this->formatPayment($payment->load(['shipment:id,shipping_reference', 'enteredby:id,name'])), 'Payment recorded.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_payment'), 403);

        $branchId = $this->resolveBranch($request);
        $payment  = Payment::where('branch_id', $branchId)
            ->with(['shipment:id,shipping_reference', 'enteredby:id,name'])
            ->findOrFail($id);

        return $this->success($this->formatPayment($payment));
    }

    private function formatPayment(Payment $p): array
    {
        return [
            'id'             => $p->id,
            'branch_id'      => $p->branch_id,
            'shipment_id'    => $p->shipment_id,
            'payment_ref'    => $p->payment_ref,
            'payment_type'   => $p->payment_type,
            'paying_type'    => $p->paying_type,
            'description'    => $p->description,
            'amount'         => $p->amount,
            'balance'        => $p->balance,
            'change'         => $p->change,
            'currency'       => $p->currency,
            'exchange_rate'  => $p->exchange_rate,
            'amount_usd'     => $p->amount_usd,
            'amount_ghs'     => $p->amount_ghs,
            'paying_method'  => $p->paying_method,
            'payment_note'   => $p->payment_note,
            'cheque_no'      => $p->cheque_no,
            'bankname'       => $p->bankname,
            'accountnumber'  => $p->accountnumber,
            'paid_on'        => $p->paid_on,
            'created_at'     => $p->created_at,
            'shipment'       => $p->shipment ? ['id' => $p->shipment->id, 'shipping_reference' => $p->shipment->shipping_reference] : null,
            'entered_by'     => $p->enteredby ? ['id' => $p->enteredby->id, 'name' => $p->enteredby->name] : null,
        ];
    }
}
