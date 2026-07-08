<?php

namespace App\Http\Controllers\Api\V1\Investment;

use App\Enums\InvestmentWebhookEventStatus;
use App\Http\Controllers\Controller;
use App\Models\InvestmentWebhookEvent;
use App\Service\InvestmentPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InvestmentPaystackWebhookController extends Controller
{
    public function __construct(protected InvestmentPaymentService $paymentService) {}

    public function handle(Request $request): Response
    {
        $secret = config('services.paystack.secret_key');
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if ($signature !== hash_hmac('sha512', $payload, $secret)) {
            return response('Unauthorized.', 401);
        }

        $event = json_decode($payload, true);

        if (! isset($event['event'], $event['data'])) {
            return response('ok');
        }

        if ($event['event'] !== 'charge.success') {
            return response('ok');
        }

        $data = $event['data'];
        $metadata = $data['metadata'] ?? [];

        if (($metadata['type'] ?? '') !== 'investment_deposit') {
            InvestmentWebhookEvent::create([
                'gateway' => 'paystack',
                'event_type' => $event['event'],
                'reference' => $data['reference'] ?? null,
                'status' => InvestmentWebhookEventStatus::ignored,
                'payload' => $event,
            ]);

            return response('ok');
        }

        try {
            $investment = $this->paymentService->verifyAndRecordPaystack($data['reference']);

            InvestmentWebhookEvent::create([
                'gateway' => 'paystack',
                'event_type' => $event['event'],
                'reference' => $data['reference'],
                'investment_id' => $investment->id,
                'status' => InvestmentWebhookEventStatus::processed,
                'payload' => $event,
            ]);
        } catch (\Throwable $e) {
            Log::error('Investment Paystack webhook error: '.$e->getMessage(), ['reference' => $data['reference']]);

            InvestmentWebhookEvent::create([
                'gateway' => 'paystack',
                'event_type' => $event['event'],
                'reference' => $data['reference'],
                'status' => InvestmentWebhookEventStatus::failed,
                'error_message' => $e->getMessage(),
                'payload' => $event,
            ]);
        }

        return response('ok');
    }
}
