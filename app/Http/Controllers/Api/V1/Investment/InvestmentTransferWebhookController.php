<?php

namespace App\Http\Controllers\Api\V1\Investment;

use App\Enums\InvestmentWebhookEventStatus;
use App\Http\Controllers\Controller;
use App\Models\InvestmentWebhookEvent;
use App\Models\InvestmentWithdrawalRequest;
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
            InvestmentWebhookEvent::create([
                'gateway' => 'paystack_transfer',
                'event_type' => $event['event'] ?? 'unknown',
                'status' => InvestmentWebhookEventStatus::ignored,
                'payload' => $event,
            ]);

            return response('ok');
        }

        $transferCode = $event['data']['transfer_code'];
        $withdrawalRequestId = InvestmentWithdrawalRequest::where('paystack_transfer_code', $transferCode)
            ->value('id');

        if (! in_array($event['event'], ['transfer.success', 'transfer.failed', 'transfer.reversed'], true)) {
            InvestmentWebhookEvent::create([
                'gateway' => 'paystack_transfer',
                'event_type' => $event['event'],
                'reference' => $transferCode,
                'investment_withdrawal_request_id' => $withdrawalRequestId,
                'status' => InvestmentWebhookEventStatus::ignored,
                'payload' => $event,
            ]);

            return response('ok');
        }

        try {
            match ($event['event']) {
                'transfer.success' => $this->transferService->handleTransferSuccess($transferCode),
                'transfer.failed', 'transfer.reversed' => $this->transferService->handleTransferFailed($transferCode),
            };

            InvestmentWebhookEvent::create([
                'gateway' => 'paystack_transfer',
                'event_type' => $event['event'],
                'reference' => $transferCode,
                'investment_withdrawal_request_id' => $withdrawalRequestId,
                'status' => InvestmentWebhookEventStatus::processed,
                'payload' => $event,
            ]);
        } catch (\Throwable $e) {
            Log::error('Investment transfer webhook error: '.$e->getMessage(), ['transfer_code' => $transferCode]);

            InvestmentWebhookEvent::create([
                'gateway' => 'paystack_transfer',
                'event_type' => $event['event'],
                'reference' => $transferCode,
                'investment_withdrawal_request_id' => $withdrawalRequestId,
                'status' => InvestmentWebhookEventStatus::failed,
                'error_message' => $e->getMessage(),
                'payload' => $event,
            ]);
        }

        return response('ok');
    }
}
