<?php

namespace App\Http\Controllers\Api\V1\Investment;

use App\Http\Controllers\Controller;
use App\Service\InvestmentPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class InvestmentStripeWebhookController extends Controller
{
    public function __construct(protected InvestmentPaymentService $paymentService) {}

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
            Log::error('Investment Stripe webhook error: '.$e->getMessage(), ['payment_intent_id' => $paymentIntentId]);
        }

        return response('ok');
    }
}
