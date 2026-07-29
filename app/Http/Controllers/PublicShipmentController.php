<?php

namespace App\Http\Controllers;

use App\Models\Shipment;

class PublicShipmentController extends Controller
{
    /**
     * No-login shipment activity page for clients who don't want a portal account —
     * reached via an unguessable public_view_token link staff generate and hand out,
     * the same trust model as the existing external shipment form.
     */
    public function show(string $token)
    {
        $shipment = Shipment::where('public_view_token', $token)
            ->with([
                'client',
                'originBranch',
                'destinationBranch',
                'receivers.items.product',
                'media' => fn ($query) => $query->latest(),
                'payments' => fn ($query) => $query->latest('paid_on'),
                'invoice',
            ])
            ->firstOrFail();

        return view('public.shipment-portal', compact('shipment'));
    }
}
