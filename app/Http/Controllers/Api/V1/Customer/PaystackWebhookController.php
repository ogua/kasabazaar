<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Services\ShipmentPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __construct(private readonly ShipmentPaymentService $payments) {}

    public function handle(Request $request): JsonResponse
    {
        // Paystack signs webhooks with the account's secret key.
        $secret = (string) (config('services.paystack.webhook_secret') ?: config('services.paystack.secret_key'));
        $signature = (string) $request->header('x-paystack-signature');

        if ($secret === '' || ! hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $data = $request->input('data', []);

        if ($request->input('event') === 'charge.success' && ! empty($data['reference'])) {
            // Verify + record (idempotent on the reference); PaymentObserver
            // fires the client confirmation alert.
            $this->payments->verify($data['reference']);
        }

        return response()->json(['status' => 'ok']);
    }
}
