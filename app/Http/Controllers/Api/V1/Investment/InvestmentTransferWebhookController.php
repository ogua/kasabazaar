<?php

namespace App\Http\Controllers\Api\V1\Investment;

use App\Http\Controllers\Controller;
use App\Service\InvestmentTransferService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvestmentTransferWebhookController extends Controller
{
    public function __construct(protected InvestmentTransferService $transferService) {}

    public function handle(Request $request): Response
    {
        $secret = config('services.paystack.secret_key');
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if ($signature !== hash_hmac('sha512', $payload, $secret)) {
            return response('Unauthorized.', 401);
        }

        $event = json_decode($payload, true);

        $this->transferService->handlePaystackTransferEvent(is_array($event) ? $event : []);

        return response('ok');
    }
}
