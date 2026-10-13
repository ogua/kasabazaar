<?php

namespace App\Filament\Vendor\Pages;

use App\Filament\Vendor\Concerns\ReadsTemporaryUploads;
use App\Services\Kasabazaar\KasabazaarClient;
use App\Services\Kasabazaar\VendorApi;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StoreSettings extends Page
{
    use ReadsTemporaryUploads;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Store';

    protected string $view = 'filament.vendor.pages.store-settings';

    public ?array $data = [];

    public ?string $logoUrl = null;

    public ?string $bannerUrl = null;

    public function mount(VendorApi $vendorApi): void
    {
        $vendor = $vendorApi->storeSettings();

        $this->logoUrl = KasabazaarClient::storageUrl($vendor['logo_path'] ?? null);
        $this->bannerUrl = KasabazaarClient::storageUrl($vendor['banner_path'] ?? null);

        $this->form->fill([
            'business_name' => $vendor['business_name'],
            'description' => $vendor['description'] ?? '',
            'phone' => $vendor['phone'] ?? '',
            'support_email' => $vendor['support_email'] ?? '',
            'payout_method' => $vendor['payout_method'] ?? '',
            'payout_details' => $vendor['payout_details'] ?? '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('newLogo')->label('Logo')->image()->avatar()->dehydrated(false),
                FileUpload::make('newBanner')->label('Banner')->image()->dehydrated(false),
                TextInput::make('business_name')->label('Business Name')->required()->maxLength(255),
                Textarea::make('description')->rows(3),
                TextInput::make('phone'),
                TextInput::make('support_email')->label('Support Email')->email(),
                Select::make('payout_method')
                    ->label('Payout Method')
                    ->options(['momo' => 'Mobile Money', 'bank' => 'Bank Transfer']),
                Textarea::make('payout_details')->label('Payout Details (account number, etc.)')->rows(2),
            ])
            ->statePath('data');
    }

    public function save(VendorApi $vendorApi, KasabazaarClient $client): void
    {
        $newLogo = $this->pendingUploads('newLogo', 'logo')[0] ?? null;
        $newBanner = $this->pendingUploads('newBanner', 'banner')[0] ?? null;
        $data = $this->form->getState();

        $vendorApi->updateStore([
            'business_name' => $data['business_name'],
            'description' => $data['description'],
            'phone' => $data['phone'],
            'support_email' => $data['support_email'],
            'payout_method' => $data['payout_method'],
            'payout_details' => $data['payout_details'],
        ]);

        if ($newLogo) {
            $store = $client->postMultipart('marketplace/vendor/store/logo', [], [$newLogo])->data;
            $this->logoUrl = $store['logo_url'] ?? $this->logoUrl;
        }

        if ($newBanner) {
            $store = $client->postMultipart('marketplace/vendor/store/banner', [], [$newBanner])->data;
            $this->bannerUrl = $store['banner_url'] ?? $this->bannerUrl;
        }

        Notification::make()->title('Store settings updated.')->success()->send();
    }
}
