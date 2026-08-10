<?php

namespace App\Filament\Vendor\Pages\Products;

use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class ListProducts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Products';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected string $view = 'filament.vendor.pages.products.list-products';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage) {
                $response = app(VendorApi::class)->products(['page' => $page, 'per_page' => $recordsPerPage]);

                $records = collect($response->data)->map(fn (array $product) => [...$product, '__key' => $product['id']]);

                return new LengthAwarePaginator(
                    $records,
                    total: $response->meta['total'] ?? $records->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                TextColumn::make('name')->searchable(false),
                TextColumn::make('sku'),
                TextColumn::make('price_ghs')
                    ->label('Price')
                    ->formatStateUsing(fn (?string $state): string => 'GHS '.number_format((float) $state, 2)),
                TextColumn::make('stock'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->recordActions([
                Action::make('toggleActive')
                    ->label(fn (array $record): string => $record['is_active'] ? 'Deactivate' : 'Activate')
                    ->icon(Heroicon::OutlinedPower)
                    ->color(fn (array $record): string => $record['is_active'] ? 'gray' : 'success')
                    ->action(fn (array $record) => app(VendorApi::class)->toggleProductActive($record['id'])),
                Action::make('edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (array $record): string => EditProduct::getUrl(['product' => $record['id']])),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('New Product')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(CreateProduct::getUrl()),
            ])
            ->emptyStateHeading('No products yet')
            ->emptyStateDescription('Add your first product to start selling.');
    }
}
