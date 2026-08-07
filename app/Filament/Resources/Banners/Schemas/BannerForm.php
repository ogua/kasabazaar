<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Banner')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public')
                            ->required(),
                        TextInput::make('link_url')
                            ->label('Link URL')
                            ->url()
                            ->nullable(),
                        Select::make('position')
                            ->options([
                                'homepage_hero' => 'Homepage Hero',
                                'homepage_promo_1' => 'Homepage Promo 1',
                                'homepage_promo_2' => 'Homepage Promo 2',
                            ])
                            ->required()
                            ->default('homepage_hero'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        DateTimePicker::make('starts_at')->nullable(),
                        DateTimePicker::make('ends_at')->nullable(),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
