<?php

namespace App\Filament\Vendor\Pages\Auctions\Concerns;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

trait HasAuctionForm
{
    public array $categoryOptions = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lot')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(fn (): array => $this->categoryOptions)
                            ->searchable(),
                        TextInput::make('condition')
                            ->placeholder('e.g. New, Used — like new')
                            ->maxLength(100),
                        Textarea::make('description')
                            ->rows(5)
                            ->helperText('Be specific: size, age, marks or faults. Accurate descriptions avoid returns.')
                            ->columnSpanFull(),
                        ...$this->additionalAuctionFormComponents(),
                    ])
                    ->columns(2),
                Section::make('Pricing')
                    ->schema([
                        TextInput::make('starting_price_ghs')->label('Starting bid (GHS)')->numeric()->minValue(1)->required(),
                        TextInput::make('bid_increment_ghs')->label('Minimum increment (GHS)')->numeric()->minValue(1)->default(10)->required(),
                        TextInput::make('reserve_price_ghs')
                            ->label('Reserve price (GHS)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Optional and hidden from bidders. Below it, the lot does not sell.'),
                    ])
                    ->columns(3),
                Section::make('Schedule')
                    ->schema([
                        DateTimePicker::make('starts_at')->label('Bidding opens')->seconds(false)->required(),
                        DateTimePicker::make('ends_at')->label('Bidding closes')->seconds(false)->required()->after('starts_at'),
                        TextInput::make('extension_minutes')
                            ->label('Anti-sniping extension (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30)
                            ->default(2),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    protected function additionalAuctionFormComponents(): array
    {
        return [];
    }

    protected function auctionPayloadFromFormState(array $data): array
    {
        return [
            'title' => $data['title'],
            'category_id' => $data['category_id'] ?: null,
            'condition' => $data['condition'] ?: null,
            'description' => $data['description'] ?: null,
            'starting_price_ghs' => (float) $data['starting_price_ghs'],
            'bid_increment_ghs' => (float) $data['bid_increment_ghs'],
            'reserve_price_ghs' => filled($data['reserve_price_ghs'] ?? null) ? (float) $data['reserve_price_ghs'] : null,
            'starts_at' => Carbon::parse($data['starts_at'])->toDateTimeString(),
            'ends_at' => Carbon::parse($data['ends_at'])->toDateTimeString(),
            'extension_minutes' => (int) ($data['extension_minutes'] ?? 2),
        ];
    }
}
