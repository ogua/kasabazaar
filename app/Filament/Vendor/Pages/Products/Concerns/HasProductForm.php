<?php

namespace App\Filament\Vendor\Pages\Products\Concerns;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

trait HasProductForm
{
    public array $categoryOptions = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Product Name')->required()->maxLength(255),
                TextInput::make('sku')->label('SKU'),
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn (): array => $this->categoryOptions)
                    ->searchable(),
                Textarea::make('description')->rows(4),
                TextInput::make('price_ghs')->label('Price (GHS)')->numeric()->required()->minValue(0),
                TextInput::make('discount_price_ghs')->label('Discount Price (GHS)')->numeric()->minValue(0),
                TextInput::make('stock')->label('Stock Quantity')->numeric()->required()->minValue(0),
                Toggle::make('is_active')->label('Active (visible in shop)'),
                ...$this->additionalProductFormComponents(),
            ])
            ->statePath('data');
    }

    protected function additionalProductFormComponents(): array
    {
        return [];
    }

    protected function productPayloadFromFormState(array $data): array
    {
        return [
            'name' => $data['name'],
            'sku' => $data['sku'] ?: null,
            'category_id' => $data['category_id'] ?: null,
            'description' => $data['description'],
            'price_ghs' => (float) $data['price_ghs'],
            'discount_price_ghs' => filled($data['discount_price_ghs'] ?? null) ? (float) $data['discount_price_ghs'] : null,
            'stock' => (int) $data['stock'],
            'is_active' => (bool) $data['is_active'],
        ];
    }
}
