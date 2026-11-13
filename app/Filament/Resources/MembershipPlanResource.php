<?php

namespace App\Filament\Resources;

use App\Enums\MembershipTier;
use App\Filament\Resources\MembershipPlanResource\Pages;
use App\Models\MembershipPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Buyer membership categories for the KASAROSE marketplace (kmarket): the dues,
 * the product discount and whether the tier unlocks exclusive auctions.
 */
class MembershipPlanResource extends Resource
{
    protected static ?string $model = MembershipPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Membership Plans';

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('tier')
                ->options(MembershipTier::class)
                ->required()
                ->unique(ignoreRecord: true)
                ->native(false),
            Forms\Components\TextInput::make('name')->required()->maxLength(60),
            Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
            Forms\Components\TextInput::make('price_ghs')->label('Dues (GHS)')->numeric()->required()->minValue(0)->prefix('GHS'),
            Forms\Components\TextInput::make('duration_months')->label('Period (months)')->numeric()->required()->minValue(1)->default(12),
            Forms\Components\TextInput::make('product_discount_percent')->label('Product discount')->numeric()->required()->minValue(0)->maxValue(100)->suffix('%'),
            Forms\Components\Toggle::make('exclusive_auctions')->label('Access to exclusive auctions'),
            Forms\Components\TagsInput::make('perks')->helperText('Shown as bullet points on the membership page.')->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('tier')->badge(),
                Tables\Columns\TextColumn::make('price_ghs')->label('Dues')->money('GHS'),
                Tables\Columns\TextColumn::make('product_discount_percent')->label('Discount')->suffix('%'),
                Tables\Columns\IconColumn::make('exclusive_auctions')->boolean()->label('Auctions'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('memberships_count')->counts('memberships')->label('Members'),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMembershipPlans::route('/'),
        ];
    }
}
