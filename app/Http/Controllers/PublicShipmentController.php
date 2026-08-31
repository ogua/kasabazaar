<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ShipmentPaymentService;
use Illuminate\Http\Request;

class PublicShipmentController extends Controller
{
    /**
     * No-login shipment activity page for clients who don't want a portal
     * account — reached via an unguessable public_view_token link staff generate
     * and hand out, the same trust model as the existing external shipment form.
     */
    public function show(string $token)
    {
        $shipment = Shipment::where('public_view_token', $token)
            ->with([
                'client',
                'originBranch',
                'destinationBranch',
                'receivers.items.product',
                'statusupdate' => fn ($query) => $query->latest('updated_at'),
                'media' => fn ($query) => $query->latest(),
                'payments' => fn ($query) => $query->latest('paid_on'),
                'invoice',
            ])
            ->firstOrFail();

        return view('public.shipment-portal', compact('shipment'));
    }

    /**
     * Start a Paystack checkout for any amount up to the outstanding balance.
     */
    public function pay(Request $request, string $token, ShipmentPaymentService $payments)
    {
        $shipment = Shipment::where('public_view_token', $token)->firstOrFail();

        $balance = round($shipment->outstanding_balance, 2);

        if ($balance <= 0) {
            return back()->with('portal_error', 'This shipment is already fully paid.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.$balance],
        ], [
            'amount.max' => 'You can pay at most $'.number_format($balance, 2).' — the outstanding balance.',
        ]);

        try {
            $url = $payments->initialize(
                $shipment,
                (float) $validated['amount'],
                route('public-shipment-paid', $token),
            );
        } catch (\Throwable $e) {
            logger()->error('Public shipment payment init failed: '.$e->getMessage());

            return back()->with('portal_error', 'We could not start the payment just now. Please try again.');
        }

        return redirect()->away($url);
    }

    /**
     * Paystack redirects here after checkout. Verify and record, then bounce
     * back to the portal with a message.
     */
    public function paid(Request $request, string $token, ShipmentPaymentService $payments)
    {
        Shipment::where('public_view_token', $token)->firstOrFail();

        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            $message = 'No payment reference was provided.';
        } else {
            try {
                $payment = $payments->verify($reference);
                $message = $payment
                    ? 'Payment received. Thank you!'
                    : 'We could not confirm that payment yet. If you were charged it will reflect shortly.';
            } catch (\Throwable $e) {
                logger()->error('Public shipment payment verify failed: '.$e->getMessage());
                $message = 'We could not confirm that payment. If you were charged it will reflect shortly.';
            }
        }

        return redirect()->route('public-shipment-view', $token)->with('portal_status', $message);
    }
}
