<?php

namespace App\Filament\Vendor\Pages\Coupons;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCoupons extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected string $view = 'filament.vendor.pages.coupons.list-coupons';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage) {
                $response = app(VendorApi::class)->coupons(['page' => $page, 'per_page' => $recordsPerPage]);

                $records = collect($response->data)->map(fn (array $coupon) => [...$coupon, '__key' => $coupon['id']]);

                return new LengthAwarePaginator(
                    $records,
                    total: $response->meta['total'] ?? $records->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                TextColumn::make('code'),
                TextColumn::make('type')->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('value')->formatStateUsing(fn ($state, array $record): string => $record['type'] === 'percentage' ? "{$state}%" : 'GHS '.number_format((float) $state, 2)),
                TextColumn::make('used_count')->label('Used'),
            ])
            ->recordActions([
                Action::make('toggleActive')
                    ->label(fn (array $record): string => $record['is_active'] ? 'Deactivate' : 'Activate')
                    ->color(fn (array $record): string => $record['is_active'] ? 'gray' : 'success')
                    ->action(fn (array $record, VendorApi $vendorApi) => $vendorApi->updateCoupon($record['id'], ['is_active' => ! $record['is_active']])),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('New Coupon')
                    ->icon(Heroicon::OutlinedPlus)
                    ->schema([
                        TextInput::make('code')->required()->maxLength(50)->extraInputAttributes(['style' => 'text-transform:uppercase'])->dehydrateStateUsing(fn (string $state) => strtoupper($state)),
                        Select::make('type')
                            ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed Amount (GHS)'])
                            ->required()
                            ->default('percentage'),
                        TextInput::make('value')->numeric()->required()->minValue(0),
                        TextInput::make('min_order_amount_ghs')->label('Minimum Order (GHS, optional)')->numeric()->minValue(0),
                        DatePicker::make('expires_at')->label('Expires At (optional)'),
                    ])
                    ->action(function (array $data, VendorApi $vendorApi) {
                        try {
                            $vendorApi->createCoupon(array_filter([
                                'code' => $data['code'],
                                'type' => $data['type'],
                                'value' => (float) $data['value'],
                                'min_order_amount_ghs' => filled($data['min_order_amount_ghs']) ? (float) $data['min_order_amount_ghs'] : null,
                                'expires_at' => $data['expires_at'] ?: null,
                            ]));

                            Notification::make()->title('Coupon created.')->success()->send();
                        } catch (KasabazaarApiException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No coupons yet');
    }
}
