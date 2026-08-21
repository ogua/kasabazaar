<?php

namespace App\Filament\Investor\Resources\InvestmentConversionResource\Pages;

use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Filament\Investor\Resources\InvestmentConversionResource;
use App\Models\InvestmentConversion;
use App\Models\InvestmentConversionSource;
use App\Service\InvestmentConversionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateInvestmentConversion extends CreateRecord
{
    protected static string $resource = InvestmentConversionResource::class;

    protected static ?string $title = 'Request a Conversion';

    /**
     * Raises a request only. Nothing on the investor's holdings moves until staff
     * approve and execute it — the same review gate withdrawal requests go through,
     * for the same reason: settling a tranche moves real ledger balances.
     */
    protected function handleRecordCreation(array $data): InvestmentConversion
    {
        $investor = auth()->user()->investor;
        $direction = InvestmentConversionDirection::from($data['direction']);
        $service = app(InvestmentConversionService::class);
        $conversionDate = now();

        $sources = collect($data['source_ids'])->map(fn ($id) => [
            'investment_id' => $id,
            'mode' => $data['mode'],
            'amount' => $data['mode'] === InvestmentConversionSourceMode::partial->value
                ? (float) ($data['amount'] ?? 0)
                : null,
        ])->all();

        // Price the request through the same path execute() will use, so an
        // ineligible selection is rejected here rather than at approval time.
        try {
            $quote = $service->quote($investor, $sources, $conversionDate);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Unable to submit this request')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        foreach ($quote['sources'] as $source) {
            if ($source['investment']->capital_type === $direction->targetCapitalType()) {
                Notification::make()
                    ->title('Unable to submit this request')
                    ->body("{$source['investment']->reference} is already a {$source['investment']->capital_type->getLabel()} holding.")
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        $conversion = DB::transaction(function () use ($investor, $direction, $data, $quote, $conversionDate) {
            $conversion = InvestmentConversion::create([
                'investor_id' => $investor->id,
                'direction' => $direction->value,
                'conversion_date' => $conversionDate->toDateString(),
                'status' => InvestmentConversionStatus::pending_approval->value,
                'requested_by_investor' => true,
                'target_contract_term_months' => $data['target_contract_term_months'],
                'target_payout_frequency' => $data['target_payout_frequency'] ?? null,
                'total_principal_rolled' => $quote['total_principal_rolled'],
                'total_interest_rolled' => $quote['total_interest_rolled'],
                'total_amount' => $quote['total_amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($quote['sources'] as $source) {
                InvestmentConversionSource::create([
                    'investment_conversion_id' => $conversion->id,
                    'source_investment_id' => $source['investment']->id,
                    'mode' => $source['mode']->value,
                    'principal_at_conversion' => $source['principal_at_conversion'],
                    'interest_at_conversion' => $source['interest_at_conversion'],
                    'amount_rolled' => $source['amount_rolled'],
                    'amount_paid_out' => $source['amount_paid_out'],
                    'remaining_balance_after' => $source['remaining_balance_after'],
                ]);
            }

            return $conversion;
        });

        Notification::make()
            ->title('Conversion request submitted')
            ->body('Our team will review it and confirm once approved. Your holdings are unchanged until then.')
            ->success()
            ->send();

        return $conversion;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
