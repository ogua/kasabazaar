<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Http\Controllers\Controller;
use App\Services\EcommercePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EcommercePaystackWebhookController extends Controller
{
    public function __construct(protected EcommercePaymentService $paymentService) {}

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

        if (($metadata['type'] ?? '') !== 'ecommerce_order_group') {
            return response('ok');
        }

        try {
            $this->paymentService->verifyAndRecordPaystack($data['reference']);
        } catch (\Throwable $e) {
            Log::error('Ecommerce Paystack webhook error: '.$e->getMessage(), ['reference' => $data['reference']]);
        }

        return response('ok');
    }
}
