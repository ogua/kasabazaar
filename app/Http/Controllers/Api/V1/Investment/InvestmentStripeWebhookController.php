<?php

namespace App\Http\Controllers\Api\V1\Investment;

use App\Enums\InvestmentWebhookEventStatus;
use App\Http\Controllers\Controller;
use App\Models\InvestmentWebhookEvent;
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

        if ($event->type !== 'checkout.session.completed') {
            return response('ok');
        }

        $session = $event->data->object;

        if (($session->metadata['type'] ?? '') !== 'investment_deposit') {
            InvestmentWebhookEvent::create([
                'gateway' => 'stripe',
                'event_type' => $event->type,
                'reference' => $session->id,
                'status' => InvestmentWebhookEventStatus::ignored,
                'payload' => $event->toArray(),
            ]);

            return response('ok');
        }

        $sessionId = $session->id;

        try {
            $investment = $this->paymentService->verifyAndRecordStripe($sessionId);

            InvestmentWebhookEvent::create([
                'gateway' => 'stripe',
                'event_type' => $event->type,
                'reference' => $sessionId,
                'investment_id' => $investment->id,
                'status' => InvestmentWebhookEventStatus::processed,
                'payload' => $event->toArray(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Investment Stripe webhook error: '.$e->getMessage(), ['session_id' => $sessionId]);

            InvestmentWebhookEvent::create([
                'gateway' => 'stripe',
                'event_type' => $event->type,
                'reference' => $sessionId,
                'status' => InvestmentWebhookEventStatus::failed,
                'error_message' => $e->getMessage(),
                'payload' => $event->toArray(),
            ]);
        }

        return response('ok');
    }
}
