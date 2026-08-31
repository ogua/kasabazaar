<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Services\PushNotificationService;
use App\Services\ShipmentNotifier;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if (! $payment->shipment_id) {
            return;
        }

        $shipment = $payment->shipment;
        if (! $shipment) {
            return;
        }

        $clientUser = User::where('client_id', $shipment->client_id)
            ->whereNotNull('client_id')
            ->first();

        if ($clientUser) {
            // Database notification
            $clientUser->notify(new PaymentReceived($payment));

            // Push notification
            app(PushNotificationService::class)->sendToUser(
                $clientUser->id,
                'Payment Received',
                "Your payment of USD {$payment->amount_usd} has been recorded.",
                ['payment_id' => $payment->id, 'type' => 'payment_received']
            );
        }

        // Email + SMS confirmation to the sender with tracking + payment links
        ShipmentNotifier::sent($shipment, 'payment_received', ['payment' => $payment]);
    }
}
