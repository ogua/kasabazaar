<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Http\Controllers\Controller;
use App\Services\EcommercePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class EcommerceStripeWebhookController extends Controller
{
    public function __construct(protected EcommercePaymentService $paymentService) {}

    public function handle(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Unauthorized.', 401);
        } catch (\Throwable $e) {
            return response('Bad request.', 400);
        }

        if ($event->type !== 'payment_intent.succeeded') {
            return response('ok');
        }

        $paymentIntentId = $event->data->object->id;

        try {
            $this->paymentService->verifyAndRecordStripe($paymentIntentId);
        } catch (\Throwable $e) {
            Log::error('Ecommerce Stripe webhook error: '.$e->getMessage(), ['payment_intent_id' => $paymentIntentId]);
        }

        return response('ok');
    }
}
