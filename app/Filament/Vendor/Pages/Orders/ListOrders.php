<?php

namespace App\Filament\Vendor\Pages\Orders;

use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class ListOrders extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Orders';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected string $view = 'filament.vendor.pages.orders.list-orders';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage, array $filters = []) {
                $response = app(VendorApi::class)->orders(array_filter([
                    'status' => $filters['status']['value'] ?? null,
                    'page' => $page,
                    'per_page' => $recordsPerPage,
                ]));

                $records = collect($response->data)->map(fn (array $order) => [...$order, '__key' => $order['id']]);

                return new LengthAwarePaginator(
                    $records,
                    total: $response->meta['total'] ?? $records->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                TextColumn::make('order_number')->label('Order'),
                TextColumn::make('user.name')->label('Customer')->default('—'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('total_ghs')
                    ->label('Total')
                    ->formatStateUsing(fn (?string $state): string => 'GHS '.number_format((float) $state, 2)),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'paid' => 'Paid',
                    'processing' => 'Processing',
                    'packed' => 'Packed',
                    'dispatched' => 'Dispatched',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ]),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => ShowOrder::getUrl(['order' => $record['id']])),
            ])
            ->emptyStateHeading('No orders yet');
    }
}
