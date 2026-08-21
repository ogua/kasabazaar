<?php

namespace App\Filament\Resources;

use App\Enums\TaxFilingType;
use App\Filament\Resources\TaxFilingResource\Pages;
use App\Models\TaxFiling;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * A vault for statutory returns already filed with the authorities. The system
 * stores and serves these so a lender can be given a complete pack in one place —
 * it does not compute Ghanaian tax, which comes from the company's accountant.
 */
class TaxFilingResource extends Resource
{
    protected static ?string $model = TaxFiling::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Tax Filings';

    protected static ?int $navigationSort = 6;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Filing')
                ->description('A return already filed with the authorities. Upload the filed document so it can be issued with the financial statements.')
                ->schema([
                    Forms\Components\TextInput::make('year')
                        ->label('Financial Year')
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue((int) now()->format('Y') + 1)
                        ->default((int) now()->format('Y') - 1)
                        ->required(),

                    Forms\Components\Select::make('filing_type')
                        ->label('Type')
                        ->options(TaxFilingType::class)
                        ->required(),

                    Forms\Components\DatePicker::make('filed_at')
                        ->label('Date Filed'),

                    Forms\Components\TextInput::make('reference')
                        ->label('Filing Reference')
                        ->maxLength(255)
                        ->helperText('The reference issued by the tax authority, if any.'),

                    Forms\Components\FileUpload::make('document_path')
                        ->label('Filed Document')
                        ->directory('tax-filings')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('year')->sortable(),

                Tables\Columns\TextColumn::make('filing_type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('filed_at')
                    ->date('M j, Y')
                    ->placeholder('Not recorded')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\IconColumn::make('document_path')
                    ->label('Document')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus'),

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('filing_type')->options(TaxFilingType::class),
                Tables\Filters\SelectFilter::make('year')
                    ->options(fn () => TaxFiling::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (TaxFiling $record) => filled($record->document_path))
                    ->url(fn (TaxFiling $record) => Storage::disk('public')->url($record->document_path))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxFilings::route('/'),
            'create' => Pages\CreateTaxFiling::route('/create'),
            'edit' => Pages\EditTaxFiling::route('/{record}/edit'),
        ];
    }
}
