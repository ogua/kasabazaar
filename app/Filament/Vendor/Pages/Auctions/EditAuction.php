<?php

namespace App\Filament\Vendor\Pages\Auctions;

use App\Filament\Vendor\Concerns\ReadsTemporaryUploads;
use App\Filament\Vendor\Pages\Auctions\Concerns\HasAuctionForm;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\KasabazaarClient;
use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;

class EditAuction extends Page
{
    use HasAuctionForm, ReadsTemporaryUploads;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.vendor.pages.auctions.auction-form';

    public string $auctionId;

    public array $lot = [];

    public array $bids = [];

    public ?array $data = [];

    public static function getRoutePath(Panel $panel): string
    {
        return '/auctions/{auction}/edit';
    }

    public function mount(VendorApi $vendorApi, string $auction): void
    {
        $this->auctionId = $auction;
        $this->categoryOptions = collect($vendorApi->categories())->pluck('name', 'id')->all();

        $this->loadAuction($vendorApi);
    }

    protected function additionalAuctionFormComponents(): array
    {
        return [
            FileUpload::make('newImages')
                ->label('Add photos')
                ->image()
                ->multiple()
                ->maxFiles(8)
                ->maxSize(4096)
                ->helperText('Up to 8 photos in total, 4 MB each. The first photo is the cover.')
                ->dehydrated(false)
                ->visible(fn (): bool => $this->isEditable())
                ->columnSpanFull(),
        ];
    }

    public function isEditable(): bool
    {
        return (bool) ($this->lot['is_editable'] ?? false);
    }

    public function save(VendorApi $vendorApi, KasabazaarClient $client): void
    {
        $newImages = $this->pendingUploads('newImages', 'image');
        $data = $this->form->getState();

        try {
            $vendorApi->updateAuction($this->auctionId, $this->auctionPayloadFromFormState($data));

            foreach ($newImages as $image) {
                $client->postMultipart("marketplace/vendor/auctions/{$this->auctionId}/images", [], [$image]);
            }
        } catch (KasabazaarApiException $e) {
            Notification::make()->title('Could not save the auction')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->loadAuction($vendorApi);
        Notification::make()->title('Auction saved.')->success()->send();
    }

    public function removeImage(VendorApi $vendorApi, int $index): void
    {
        try {
            $vendorApi->deleteAuctionImage($this->auctionId, $index);
        } catch (KasabazaarApiException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }

        $this->loadAuction($vendorApi);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit for review')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('success')
                ->visible(fn (): bool => ($this->lot['status'] ?? null) === 'draft')
                ->requiresConfirmation()
                ->modalDescription('Our team checks the listing before it goes live. You can still edit it while it waits — editing sends it back to draft.')
                ->action(function (VendorApi $vendorApi): void {
                    try {
                        $vendorApi->submitAuction($this->auctionId);
                        Notification::make()->title('Submitted for review.')->success()->send();
                    } catch (KasabazaarApiException $e) {
                        Notification::make()->title('Not submitted')->body($e->getMessage())->danger()->send();
                    }

                    $this->loadAuction($vendorApi);
                }),
            Action::make('delete')
                ->label('Delete draft')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn (): bool => $this->isEditable())
                ->requiresConfirmation()
                ->action(function (VendorApi $vendorApi): void {
                    $vendorApi->deleteAuction($this->auctionId);
                    Notification::make()->title('Auction deleted.')->success()->send();
                    $this->redirect(ListAuctions::getUrl(), navigate: false);
                }),
        ];
    }

    private function loadAuction(VendorApi $vendorApi): void
    {
        $this->lot = $vendorApi->auction($this->auctionId);
        $this->bids = $this->isEditable() ? [] : $vendorApi->auctionBids($this->auctionId);

        $this->form->fill([
            'title' => $this->lot['title'],
            'category_id' => $this->lot['category_id'] ?? null,
            'condition' => $this->lot['condition'] ?? '',
            'description' => $this->lot['description'] ?? '',
            'starting_price_ghs' => $this->lot['starting_price_ghs'],
            'bid_increment_ghs' => $this->lot['bid_increment_ghs'],
            'reserve_price_ghs' => $this->lot['reserve_price_ghs'] ?? null,
            'starts_at' => $this->lot['starts_at'],
            'ends_at' => $this->lot['ends_at'],
            'extension_minutes' => $this->lot['extension_minutes'] ?? 2,
        ]);

        $this->form->disabled(! $this->isEditable());
    }

    public function getTitle(): string
    {
        return $this->lot['title'] ?? 'Auction';
    }
}
