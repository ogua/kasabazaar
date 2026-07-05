<?php

namespace App\Http\Controllers\Api\V1\Investment;

use App\Http\Controllers\Controller;
use App\Service\InvestmentTransferService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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

        if (! isset($event['event'], $event['data']['transfer_code'])) {
            return response('ok');
        }

        $transferCode = $event['data']['transfer_code'];

        try {
            match ($event['event']) {
                'transfer.success' => $this->transferService->handleTransferSuccess($transferCode),
                'transfer.failed', 'transfer.reversed' => $this->transferService->handleTransferFailed($transferCode),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Investment transfer webhook error: '.$e->getMessage(), ['transfer_code' => $transferCode]);
        }

        return response('ok');
    }
}
