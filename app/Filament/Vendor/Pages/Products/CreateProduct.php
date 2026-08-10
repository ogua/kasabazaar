<?php

namespace App\Filament\Vendor\Pages\Products;

use App\Filament\Vendor\Pages\Products\Concerns\HasProductForm;
use App\Services\Kasabazaar\VendorApi;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CreateProduct extends Page
{
    use HasProductForm;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.vendor.pages.products.product-form';

    public ?array $data = [];

    public function mount(VendorApi $vendorApi): void
    {
        $this->categoryOptions = collect($vendorApi->categories())->pluck('name', 'id')->all();

        $this->form->fill(['is_active' => true, 'stock' => 0]);
    }

    public function save(VendorApi $vendorApi): void
    {
        $product = $vendorApi->createProduct($this->productPayloadFromFormState($this->form->getState()));

        Notification::make()->title('Product created.')->success()->send();

        $this->redirect(EditProduct::getUrl(['product' => $product['id']]), navigate: false);
    }

    public function getTitle(): string
    {
        return 'Add Product';
    }
}
