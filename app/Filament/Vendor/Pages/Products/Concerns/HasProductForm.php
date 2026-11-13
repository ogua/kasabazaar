<?php

namespace App\Filament\Vendor\Pages\Products\Concerns;

use App\Support\ProductCondition;
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
                Select::make('condition')
                    ->label('Item Condition')
                    ->options(ProductCondition::options())
                    ->default(ProductCondition::DEFAULT)
                    ->required()
                    ->native(false)
                    ->live()
                    ->helperText(fn (?string $state): string => ProductCondition::for($state)['description']),
                Textarea::make('description')->rows(4),
                Toggle::make('ships_from_usa')
                    ->label('This item is held in the USA')
                    ->helperText('Buyers in Ghana receive it through KASAROSE USA-to-Ghana shipping, priced by weight. Set the weight below.'),
                TextInput::make('weight')
                    ->label('Weight (kg)')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Used to price shipping from the USA to Ghana with KASAROSE.'),
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
            'condition' => $data['condition'] ?? ProductCondition::DEFAULT,
            'description' => $data['description'],
            'ships_from_usa' => (bool) ($data['ships_from_usa'] ?? false),
            'weight' => filled($data['weight'] ?? null) ? (float) $data['weight'] : null,
            'price_ghs' => (float) $data['price_ghs'],
            'discount_price_ghs' => filled($data['discount_price_ghs'] ?? null) ? (float) $data['discount_price_ghs'] : null,
            'stock' => (int) $data['stock'],
            'is_active' => (bool) $data['is_active'],
        ];
    }
}
