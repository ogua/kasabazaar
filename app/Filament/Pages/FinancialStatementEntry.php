<?php

namespace App\Filament\Pages;

use App\Enums\FiscalPeriodSource;
use App\Enums\FiscalPeriodStatus;
use App\Models\AccountBalance;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Service\FinancialStatementService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Keys in a year's closing balances from the accountant's books, for years that
 * predate this system holding any transactions. Once locked, the year's figures
 * become the source for the P&L and Balance Sheet pages instead of live records.
 */
class FinancialStatementEntry extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Financial Statement Entry';

    protected static ?string $navigationLabel = 'Prior-Year Entry';

    protected static string $view = 'filament.pages.financial-statement-entry';

    public int $selectedYear;

    /** @var array<string, float|string|null> keyed by chart_of_accounts.id */
    public array $balances = [];

    /** The currency the accountant's figures for this year are stated in. */
    public string $entryCurrency = 'GHS';

    /** GHS per USD at the year end, used to translate the figures above. */
    public ?string $closingRate = null;

    public function mount(): void
    {
        $this->selectedYear = FinancialStatementService::firstRecordedYear() - 1;
        $this->loadBalances();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedYear')
                    ->label('Financial Year')
                    ->options(collect(range(FinancialStatementService::firstRecordedYear(), FinancialStatementService::firstRecordedYear() - 6))
                        ->mapWithKeys(fn (int $year) => [$year => (string) $year])
                        ->all())
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadBalances())
                    ->required(),

                // Without this the keyed figures would be read as USD. A Ghana set of
                // accounts is kept in Cedis, so the statements would be overstated by
                // the exchange rate and still labelled USD.
                Select::make('entryCurrency')
                    ->label('Figures are stated in')
                    ->options(['GHS' => 'Ghana Cedis (GHS)', 'USD' => 'US Dollars (USD)'])
                    ->live()
                    ->required()
                    ->helperText('The currency of the accounts you are keying in.'),

                TextInput::make('closingRate')
                    ->label('Year-end rate (GHS per USD 1.00)')
                    ->numeric()
                    ->step('0.0001')
                    ->live(onBlur: true)
                    ->required(fn () => $this->entryCurrency === 'GHS')
                    ->visible(fn () => $this->entryCurrency === 'GHS')
                    ->helperText('The rate at '.$this->selectedYear.' year end. Statements present in USD and translate these figures at this rate.'),
            ])
            ->columns(3);
    }

    public function loadBalances(): void
    {
        $period = $this->period();
        $this->entryCurrency = $period->entry_currency ?: 'GHS';
        $this->closingRate = $period->closing_exchange_rate
            ? (string) $period->closing_exchange_rate
            : null;

        $existing = AccountBalance::where('fiscal_year', $this->selectedYear)
            ->pluck('closing_balance', 'chart_of_account_id');

        $this->balances = $this->accounts()
            ->mapWithKeys(fn (ChartOfAccount $account) => [
                $account->id => $existing[$account->id] ?? null,
            ])
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ChartOfAccount>
     */
    public function accounts()
    {
        return ChartOfAccount::active()
            ->orderBy('sort_order')
            ->get();
    }

    public function period(): FiscalPeriod
    {
        return app(FinancialStatementService::class)->periodFor($this->selectedYear);
    }

    /**
     * @return array{debits: float, credits: float, difference: float, balances: bool}
     */
    public function trialBalance(): array
    {
        $accounts = $this->accounts()->keyBy('id');
        $debits = 0.0;
        $credits = 0.0;

        foreach ($this->balances as $accountId => $amount) {
            $account = $accounts->get($accountId);

            if (! $account || ! filled($amount)) {
                continue;
            }

            if ($account->type->isDebitNormal()) {
                $debits += (float) $amount;
            } else {
                $credits += (float) $amount;
            }
        }

        $difference = round($debits - $credits, 2);

        return [
            'debits' => round($debits, 2),
            'credits' => round($credits, 2),
            'difference' => $difference,
            'balances' => abs($difference) < 0.01,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Balances')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),

            Action::make('lock')
                ->label('Lock Year')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Locking marks the year as final. Its balances can no longer be edited here, and the statements will present it as entered from the accountant\'s books.')
                ->visible(fn () => $this->period()->status !== FiscalPeriodStatus::locked)
                ->action('lock'),
        ];
    }

    public function save(): void
    {
        $period = $this->period();

        if ($period->status === FiscalPeriodStatus::locked) {
            Notification::make()
                ->title('This year is locked')
                ->body('Unlock it before editing its balances.')
                ->danger()
                ->send();

            return;
        }

        foreach ($this->balances as $accountId => $amount) {
            if (! filled($amount)) {
                AccountBalance::where('fiscal_year', $this->selectedYear)
                    ->where('chart_of_account_id', $accountId)
                    ->delete();

                continue;
            }

            AccountBalance::updateOrCreate(
                ['fiscal_year' => $this->selectedYear, 'chart_of_account_id' => $accountId],
                [
                    // These are closing positions taken straight from the accountant's
                    // books, so the whole figure is recorded as the year's movement and
                    // AccountBalance::saving() derives closing_balance from it.
                    'opening_balance' => 0,
                    'movement' => (float) $amount,
                    'entered_by' => auth()->id(),
                ]
            );
        }

        // A year that has been keyed in must read from those figures, not from live
        // records that do not exist for it. The entry currency and rate travel with
        // the year so the statements translate it correctly every time they render.
        $period->update([
            'source' => FiscalPeriodSource::manual->value,
            'entry_currency' => $this->entryCurrency,
            'closing_exchange_rate' => $this->entryCurrency === 'GHS' && filled($this->closingRate)
                ? (float) $this->closingRate
                : $period->closing_exchange_rate,
        ]);

        $check = $this->trialBalance();

        Notification::make()
            ->title('Balances saved')
            ->body($check['balances']
                ? 'The trial balance articulates.'
                : 'Saved, but debits and credits differ by $'.number_format(abs($check['difference']), 2).'. The balance sheet will not balance until this is resolved.')
            ->color($check['balances'] ? 'success' : 'warning')
            ->send();
    }

    public function lock(): void
    {
        if ($this->entryCurrency === 'GHS' && ! filled($this->closingRate)) {
            Notification::make()
                ->title('A year-end exchange rate is required')
                ->body('These figures are in Cedis and the statements present in USD, so a rate is needed to translate them.')
                ->danger()
                ->send();

            return;
        }

        $check = $this->trialBalance();

        if (! $check['balances']) {
            Notification::make()
                ->title('Cannot lock an unbalanced year')
                ->body('Debits and credits differ by $'.number_format(abs($check['difference']), 2).'. Resolve it before locking.')
                ->danger()
                ->send();

            return;
        }

        $this->period()->update([
            'status' => FiscalPeriodStatus::locked->value,
            'source' => FiscalPeriodSource::manual->value,
            'locked_by' => auth()->id(),
            'locked_at' => now(),
        ]);

        Notification::make()->title('Year locked')->success()->send();
    }
}
