<?php

namespace App\Filament\Resources;

use App\Enums\AuctionOutcome;
use App\Enums\AuctionStatus;
use App\Exceptions\AuctionException;
use App\Filament\Resources\AuctionResource\Pages;
use App\Filament\Resources\AuctionResource\RelationManagers\BidsRelationManager;
use App\Models\Auction;
use App\Models\EcommerceCategory;
use App\Models\Vendor;
use App\Services\AuctionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuctionResource extends Resource
{
    protected static ?string $model = Auction::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 3;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lot')
                    ->description('What is being sold. Auction lots are separate from shop products and never appear in the catalogue.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('vendor_id')
                            ->label('Seller')
                            ->options(fn () => Vendor::active()->orderBy('business_name')->pluck('business_name', 'id'))
                            ->default(fn () => Vendor::platform()->id)
                            ->searchable()
                            ->required()
                            ->helperText('Receives the proceeds and fulfils the order.'),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(fn () => EcommerceCategory::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('condition')
                            ->placeholder('e.g. New, Used — like new, Refurbished')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('description')
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image_paths')
                            ->label('Photos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(8)
                            ->maxSize(4096)
                            ->disk('public')
                            ->directory('auction-images')
                            ->visibility('public')
                            ->helperText('The first photo is the cover. Up to 8 photos, 4 MB each.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('starting_price_ghs')
                            ->label('Starting bid (GHS)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->disabled(fn (?Auction $record) => $record?->bids_count > 0),
                        Forms\Components\TextInput::make('bid_increment_ghs')
                            ->label('Minimum increment (GHS)')
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->required()
                            ->disabled(fn (?Auction $record) => $record?->bids_count > 0),
                        Forms\Components\TextInput::make('reserve_price_ghs')
                            ->label('Reserve price (GHS)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->gte('starting_price_ghs')
                            ->helperText('Optional and never shown to bidders. Below it, the lot does not sell.')
                            ->disabled(fn (?Auction $record) => $record?->bids_count > 0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Bidding opens')
                            ->seconds(false)
                            ->required()
                            ->default(now()->addHour()->startOfHour())
                            ->disabled(fn (?Auction $record) => $record?->bids_count > 0),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Bidding closes')
                            ->seconds(false)
                            ->required()
                            ->after('starts_at')
                            ->default(now()->addDays(7)->startOfHour())
                            ->disabled(fn (?Auction $record) => $record?->bids_count > 0),
                        Forms\Components\TextInput::make('extension_minutes')
                            ->label('Anti-sniping extension (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30)
                            ->default(2)
                            ->required()
                            ->helperText('A bid this close to the end pushes the end time out by the same amount. 0 turns it off.'),
                        Forms\Components\Select::make('status')
                            ->options([
                                AuctionStatus::Draft->value => 'Draft — hidden from shoppers',
                                AuctionStatus::Published->value => 'Published — visible, opens on schedule',
                            ])
                            ->default(AuctionStatus::Draft->value)
                            ->required()
                            ->visible(fn (?Auction $record) => $record === null
                                || in_array($record->status, [AuctionStatus::Draft, AuctionStatus::Published], true))
                            ->disabled(fn (?Auction $record) => $record?->bids_count > 0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('')
                    ->getStateUsing(fn (Auction $record) => $record->image_paths[0] ?? null)
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('images/kasarose-logo.png')),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->description(fn (Auction $record) => $record->vendor?->business_name),
                Tables\Columns\TextColumn::make('state')
                    ->label('State')
                    ->getStateUsing(fn (Auction $record) => ucfirst($record->state()))
                    ->badge()
                    ->color(fn (Auction $record) => match ($record->state()) {
                        Auction::STATE_LIVE => 'success',
                        Auction::STATE_UPCOMING => 'info',
                        Auction::STATE_ENDED => 'gray',
                        Auction::STATE_CANCELLED => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('current_price_ghs')
                    ->label('Current bid')
                    ->money('GHS')
                    ->placeholder('—')
                    ->description(fn (Auction $record) => 'Start '.number_format((float) $record->starting_price_ghs, 2)),
                Tables\Columns\TextColumn::make('bids_count')
                    ->label('Bids')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Closes')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outcome')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('winner.name')
                    ->label('Winner')
                    ->placeholder('—')
                    ->description(fn (Auction $record) => match (true) {
                        $record->outcome !== AuctionOutcome::Sold => null,
                        $record->isPaid() => 'Paid',
                        $record->payment_due_at?->isPast() => 'Payment overdue',
                        default => 'Awaiting payment',
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(AuctionStatus::class),
                Tables\Filters\Filter::make('live')
                    ->label('Live now')
                    ->query(fn (Builder $query) => $query->live()),
                Tables\Filters\SelectFilter::make('outcome')
                    ->options(AuctionOutcome::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->modalDescription('Bidders will no longer see this auction and no further bids are accepted. Existing bids stay on record.')
                    ->visible(fn (Auction $record) => in_array($record->status, [AuctionStatus::Draft, AuctionStatus::Published], true))
                    ->action(function (Auction $record, AuctionService $auctionService) {
                        try {
                            $auctionService->cancel($record);
                            Notification::make()->title('Auction cancelled')->success()->send();
                        } catch (AuctionException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('closeNow')
                    ->label('Settle now')
                    ->icon('heroicon-o-flag')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->modalDescription('Records the result for an auction whose end time has passed. The scheduler normally does this within a minute.')
                    ->visible(fn (Auction $record) => $record->status === AuctionStatus::Published && $record->ends_at->isPast())
                    ->action(function (Auction $record, AuctionService $auctionService) {
                        $auctionService->close($record);
                        Notification::make()->title('Auction settled')->success()->send();
                    }),
            ])
            ->defaultSort('ends_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            BidsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuctions::route('/'),
            'create' => Pages\CreateAuction::route('/create'),
            'edit' => Pages\EditAuction::route('/{record}/edit'),
        ];
    }
}
