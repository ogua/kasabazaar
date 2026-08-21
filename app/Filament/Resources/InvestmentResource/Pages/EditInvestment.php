<?php

namespace App\Filament\Resources\InvestmentResource\Pages;

use App\Enums\InvestmentInterestPayoutStatus;
use App\Enums\InvestmentTransactionType;
use App\Filament\Resources\InvestmentResource;
use App\Models\InvestmentRateOverride;
use App\Service\InvestmentInterestPayoutService;
use App\Service\InvestmentInterestService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvestment extends EditRecord
{
    protected static string $resource = InvestmentResource::class;

    protected ?float $initialAnnualRate = null;

    protected function getHeaderActions(): array
    {
        return [
            InvestmentResource::postInterestHeaderAction(),
            InvestmentResource::convertCapitalHeaderAction(),
            Actions\ViewAction::make(),

            // Only safe to delete outright when nothing about it has become real yet —
            // no posted contribution/interest/payout. Once funded, use "Reverse & Close"
            // instead, which unwinds the ledger rather than silently orphaning it.
            Actions\DeleteAction::make()
                ->visible(fn () => ! $this->record->transactions()->where('posted', true)->exists())
                ->before(function () {
                    $this->record->transactions()->delete();
                    $this->record->rateOverrides()->delete();
                    $this->record->interestPayouts()->delete();
                }),

            $this->reverseAndCloseAction(),
        ];
    }

    /**
     * For a funded/active investment that was recorded in error: reverses every
     * posted interest_credit and every processing/paid interest_payout back to zero
     * effect (preserving the audit trail — see InvestmentInterestService::
     * reverseInterestCredit() and InvestmentInterestPayoutService::reversePayout()),
     * logs the reason, then soft-deletes the record. Does not — and cannot — claw
     * back cash already sent to the investor; that is a manual banking matter.
     */
    protected function reverseAndCloseAction(): Actions\Action
    {
        return Actions\Action::make('reverseAndClose')
            ->label('Reverse & Close')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->visible(fn () => $this->record->transactions()->where('posted', true)->exists())
            ->requiresConfirmation()
            ->modalDescription('This unwinds all posted interest for this investment and removes it from the active list. It does NOT claw back money already sent to the investor — if cash has already left the company via Paystack or manual transfer, resolve that separately.')
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->placeholder('e.g. Recorded against the wrong investor, duplicate entry...'),
            ])
            ->action(function (array $data) {
                $record = $this->record;
                $editor = auth()->user();

                $interestService = app(InvestmentInterestService::class);
                foreach ($record->transactions()
                    ->where('type', InvestmentTransactionType::interest_credit->value)
                    ->where('posted', true)
                    ->where('credit', '>', 0)
                    ->get() as $transaction) {
                    $interestService->reverseInterestCredit($transaction, $editor, $data['reason']);
                }

                $payoutService = app(InvestmentInterestPayoutService::class);
                foreach ($record->interestPayouts()
                    ->whereIn('status', [InvestmentInterestPayoutStatus::processing->value, InvestmentInterestPayoutStatus::paid->value])
                    ->get() as $payout) {
                    $payoutService->reversePayout($payout, $editor, $data['reason']);
                }

                $record->update([
                    'notes' => trim(($record->notes ?? '')."\n[".now()->format('M j, Y').'] Reversed & closed by '.$editor->name.": {$data['reason']}"),
                ]);
                $record->delete();

                Notification::make()
                    ->title('Investment reversed and closed')
                    ->success()
                    ->send();

                $this->redirect(InvestmentResource::getUrl('index'));
            });
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->initialAnnualRate = filled($data['initial_annual_rate'] ?? null) ? (float) $data['initial_annual_rate'] : null;
        unset($data['initial_annual_rate']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->initialAnnualRate === null) {
            return;
        }

        InvestmentRateOverride::updateOrCreate(
            [
                'investment_id' => $this->record->id,
                'year' => Carbon::parse($this->record->start_date)->year,
            ],
            [
                'annual_rate' => $this->initialAnnualRate,
                'created_by' => auth()->id(),
                'notes' => 'Updated via investment edit form.',
            ]
        );
    }
}
