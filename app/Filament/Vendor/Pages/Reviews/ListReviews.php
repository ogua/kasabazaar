<?php

namespace App\Filament\Vendor\Pages\Reviews;

use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class ListReviews extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Store';

    protected string $view = 'filament.vendor.pages.reviews.list-reviews';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage) {
                $response = app(VendorApi::class)->reviews(['page' => $page, 'per_page' => $recordsPerPage]);

                $records = collect($response->data)->map(fn (array $review) => [...$review, '__key' => $review['id']]);

                return new LengthAwarePaginator(
                    $records,
                    total: $response->meta['total'] ?? $records->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                TextColumn::make('user.name')->label('Customer')->default('Customer'),
                TextColumn::make('product.name')->label('Product'),
                TextColumn::make('rating')->formatStateUsing(fn ($state): string => str_repeat('★', (int) $state)),
                TextColumn::make('body')->label('Review')->wrap(),
                TextColumn::make('vendor_reply')->label('Your Reply')->placeholder('—')->wrap(),
            ])
            ->recordActions([
                Action::make('reply')
                    ->visible(fn (array $record): bool => empty($record['vendor_reply']))
                    ->schema([
                        Textarea::make('reply')->label('Your Reply')->required()->rows(3),
                    ])
                    ->action(function (array $record, array $data, VendorApi $vendorApi) {
                        $vendorApi->replyToReview($record['id'], $data['reply']);
                        Notification::make()->title('Reply posted.')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No reviews yet');
    }
}
