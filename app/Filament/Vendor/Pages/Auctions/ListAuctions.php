<?php

namespace App\Filament\Vendor\Pages\Auctions;

use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * The vendor's auction lots. Drafts are edited here and submitted for review;
 * staff publish approved lots, after which they are read-only.
 */
class ListAuctions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Auctions';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.vendor.pages.auctions.list-auctions';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage) {
                $response = app(VendorApi::class)->auctions(['page' => $page, 'per_page' => $recordsPerPage]);

                $records = collect($response->data)->map(fn (array $auction) => [...$auction, '__key' => $auction['id']]);

                return new LengthAwarePaginator($records, total: $response->meta['total'] ?? $records->count(), perPage: $recordsPerPage, currentPage: $page);
            })
            ->columns([
                TextColumn::make('title')->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (array $record): string => self::statusLabel($record))
                    ->color(fn (array $record): string => match (true) {
                        $record['status'] === 'pending_review' => 'warning',
                        $record['status'] === 'draft' && filled($record['review_notes']) => 'danger',
                        $record['state'] === 'live' => 'success',
                        $record['state'] === 'upcoming' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('current_price_ghs')
                    ->label('Current bid')
                    ->formatStateUsing(fn (array $record): string => 'GHS '.number_format((float) ($record['current_price_ghs'] ?? $record['starting_price_ghs']), 2)),
                TextColumn::make('bids_count')->label('Bids'),
                TextColumn::make('ends_at')
                    ->label('Closes')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::parse($state)->timezone(config('app.timezone'))->format('j M Y, H:i') : '—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(fn (array $record): string => $record['is_editable'] ? 'Edit' : 'View')
                    ->icon(fn (array $record) => $record['is_editable'] ? Heroicon::OutlinedPencilSquare : Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => EditAuction::getUrl(['auction' => $record['id']])),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('New Auction')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(CreateAuction::getUrl()),
            ])
            ->emptyStateHeading('No auctions yet')
            ->emptyStateDescription('Auction one-off or high-value items. We review every lot before it goes live.');
    }

    public static function statusLabel(array $record): string
    {
        return match (true) {
            $record['status'] === 'draft' && filled($record['review_notes'] ?? null) => 'Changes requested',
            $record['status'] === 'draft' => 'Draft',
            $record['status'] === 'pending_review' => 'Awaiting review',
            $record['status'] === 'cancelled' => 'Cancelled',
            $record['state'] === 'ended' => match ($record['outcome'] ?? null) {
                'sold' => $record['is_paid'] ? 'Sold · paid' : 'Sold · awaiting payment',
                'reserve_not_met' => 'Ended · reserve not met',
                'no_bids' => 'Ended · no bids',
                'unpaid' => 'Ended · unpaid',
                default => 'Ended',
            },
            default => ucfirst($record['state']),
        };
    }
}
