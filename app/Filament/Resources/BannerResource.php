<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Storefront banners for the KASAROSE marketplace (kmarket). The storefront
 * renders title, subtitle and button as live text over the image, so upload
 * artwork WITHOUT baked-in headline text and keep the left ~45% calm.
 */
class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Storefront Banners';

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Artwork')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Image')
                            ->image()
                            ->required()
                            ->maxSize(4096)
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios(['9:5'])
                            ->helperText('1440 × 800 px (9:5). No text in the image — the headline and button are added by the storefront.')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('position')
                            ->options(Banner::POSITIONS)
                            ->default('homepage_hero')
                            ->required(),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers show first.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Text overlay')
                    ->schema([
                        Forms\Components\TextInput::make('eyebrow')
                            ->label('Eyebrow')
                            ->placeholder('e.g. Live now')
                            ->maxLength(40),
                        Forms\Components\TextInput::make('title')
                            ->label('Headline')
                            ->required()
                            ->maxLength(80),
                        Forms\Components\TextInput::make('subtitle')
                            ->label('Supporting line')
                            ->maxLength(160)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('cta_label')
                            ->label('Button label')
                            ->placeholder('e.g. Start bidding')
                            ->maxLength(30),
                        Forms\Components\TextInput::make('link_url')
                            ->label('Button / banner link')
                            ->placeholder('/auctions or https://…')
                            ->maxLength(255)
                            ->helperText('A path on the storefront (/shop) or a full URL.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Display window')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Show from')
                            ->seconds(false)
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Show until')
                            ->seconds(false)
                            ->nullable()
                            ->after('starts_at'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->width(144)
                    ->height(80),
                Tables\Columns\TextColumn::make('title')
                    ->label('Headline')
                    ->searchable()
                    ->description(fn (Banner $record) => $record->subtitle),
                Tables\Columns\TextColumn::make('position')
                    ->formatStateUsing(fn (string $state) => Banner::POSITIONS[$state] ?? $state)
                    ->badge()
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Until')
                    ->dateTime('j M Y')
                    ->placeholder('No end'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->options(Banner::POSITIONS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
