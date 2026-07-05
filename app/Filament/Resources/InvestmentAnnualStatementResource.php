<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentAnnualStatementResource\Pages;
use App\Models\InvestmentAnnualStatement;
use App\Service\AnnualInvestmentStatementService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class InvestmentAnnualStatementResource extends Resource
{
    protected static ?string $model = InvestmentAnnualStatement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Annual Statements';

    protected static ?int $navigationSort = 6;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('investor_id')
                    ->label('Investor')
                    ->relationship('investor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('year')
                    ->numeric()
                    ->required()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year - 1),

                Forms\Components\Textarea::make('business_updates')
                    ->label('Significant Business Updates')
                    ->columnSpanFull()
                    ->rows(5),

                Forms\Components\Hidden::make('generated_by')
                    ->default(fn () => Filament::auth()->id()),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->sortable(),

                Tables\Columns\IconColumn::make('sent_at')
                    ->label('Sent')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->sent_at !== null),

                Tables\Columns\TextColumn::make('generated_at')
                    ->dateTime('M d, Y')
                    ->toggleable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('generateAndSend')
                    ->label('Generate & Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['generated_at' => now()]);

                        if (AnnualInvestmentStatementService::sendEmail($record)) {
                            Notification::make()->title('Statement generated and emailed')->success()->send();
                        } else {
                            Notification::make()->title('Statement generated, but email could not be sent (no investor email on file)')->warning()->send();
                        }
                    }),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn ($record) => URL::temporarySignedRoute('investment-annual-statement-download', now()->addDay(), $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentAnnualStatements::route('/'),
            'create' => Pages\CreateInvestmentAnnualStatement::route('/create'),
            'edit' => Pages\EditInvestmentAnnualStatement::route('/{record}/edit'),
        ];
    }
}
