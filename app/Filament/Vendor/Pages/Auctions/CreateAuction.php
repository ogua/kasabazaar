<?php

namespace App\Filament\Vendor\Pages\Auctions;

use App\Filament\Vendor\Pages\Auctions\Concerns\HasAuctionForm;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\VendorApi;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CreateAuction extends Page
{
    use HasAuctionForm;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.vendor.pages.auctions.auction-form';

    public ?array $data = [];

    public function mount(VendorApi $vendorApi): void
    {
        $this->categoryOptions = collect($vendorApi->categories())->pluck('name', 'id')->all();

        $this->form->fill([
            'bid_increment_ghs' => 10,
            'extension_minutes' => 2,
            'starts_at' => now()->addDay()->startOfHour(),
            'ends_at' => now()->addDays(8)->startOfHour(),
        ]);
    }

    public function save(VendorApi $vendorApi): void
    {
        try {
            $auction = $vendorApi->createAuction($this->auctionPayloadFromFormState($this->form->getState()));
        } catch (KasabazaarApiException $e) {
            Notification::make()->title('Could not save the auction')->body($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Draft saved. Add photos, then submit it for review.')->success()->send();

        $this->redirect(EditAuction::getUrl(['auction' => $auction['id']]), navigate: false);
    }

    public function getTitle(): string
    {
        return 'New Auction';
    }
}
