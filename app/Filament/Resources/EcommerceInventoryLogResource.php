<?php

namespace App\Filament\Resources;

use App\Enums\EcommerceInventoryLogType;
use App\Filament\Resources\EcommerceInventoryLogResource\Pages;
use App\Models\EcommerceInventoryLog;
use App\Models\EcommerceProduct;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EcommerceInventoryLogResource extends Resource
{
    protected static ?string $model = EcommerceInventoryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = true;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Change')
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').$state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_before')
                    ->label('Before')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_after')
                    ->label('After')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(40),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(EcommerceInventoryLogType::class),
                Tables\Filters\SelectFilter::make('ecommerce_product_id')
                    ->label('Product')
                    ->options(function () {
                        $tenantId = Filament::getTenant()?->id;

                        return EcommerceProduct::where('branch_id', $tenantId)->pluck('name', 'id');
                    })
                    ->searchable(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEcommerceInventoryLogs::route('/'),
        ];
    }
}
