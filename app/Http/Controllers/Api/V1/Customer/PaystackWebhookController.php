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
        $secret = config('services.paystack.webhook_secret');
        $signature = $request->header('x-paystack-signature');

        if ($secret && $signature !== hash_hmac('sha512', $request->getContent(), $secret)) {
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
