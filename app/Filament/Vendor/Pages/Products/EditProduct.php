<?php

namespace App\Filament\Vendor\Pages\Products;

use App\Filament\Vendor\Pages\Products\Concerns\HasProductForm;
use App\Services\Kasabazaar\KasabazaarClient;
use App\Services\Kasabazaar\VendorApi;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;

class EditProduct extends Page
{
    use HasProductForm;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.vendor.pages.products.product-form';

    public string $productId;

    public array $images = [];

    public ?array $data = [];

    public static function getRoutePath(Panel $panel): string
    {
        return '/products/{product}/edit';
    }

    public function mount(VendorApi $vendorApi, string $product): void
    {
        $this->productId = $product;
        $this->categoryOptions = collect($vendorApi->categories())->pluck('name', 'id')->all();

        $item = $vendorApi->product($product);
        $this->images = $item['images'] ?? [];

        $this->form->fill([
            'name' => $item['name'],
            'sku' => $item['sku'] ?? '',
            'category_id' => $item['category']['id'] ?? null,
            'description' => $item['description'] ?? '',
            'price_ghs' => $item['price_ghs'],
            'discount_price_ghs' => $item['discount_price_ghs'] ?? null,
            'stock' => $item['stock'],
            'is_active' => (bool) $item['is_active'],
        ]);
    }

    protected function additionalProductFormComponents(): array
    {
        return [
            FileUpload::make('newImage')
                ->label('New Image')
                ->image()
                ->dehydrated(false),
        ];
    }

    public function save(VendorApi $vendorApi, KasabazaarClient $client): void
    {
        $data = $this->form->getState();
        $newImage = $data['newImage'] ?? null;
        unset($data['newImage']);

        $vendorApi->updateProduct($this->productId, $this->productPayloadFromFormState($data));

        if ($newImage) {
            $client->postMultipart("marketplace/vendor/products/{$this->productId}/images", [], [
                ['name' => 'image', 'contents' => fopen($newImage->getRealPath(), 'r'), 'filename' => $newImage->getClientOriginalName()],
            ]);
            $this->images = $vendorApi->product($this->productId)['images'] ?? [];
        }

        Notification::make()->title('Product saved.')->success()->send();
    }

    public function getTitle(): string
    {
        return 'Edit Product';
    }
}
