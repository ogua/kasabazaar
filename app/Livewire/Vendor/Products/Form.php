<?php

namespace App\Livewire\Vendor\Products;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\KasabazaarClient;
use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?string $productId = null;

    public string $name = '';

    public string $sku = '';

    public string $category_id = '';

    public string $description = '';

    public string $price_ghs = '';

    public string $discount_price_ghs = '';

    public string $stock = '0';

    public bool $is_active = true;

    public array $categories = [];

    public $newImage;

    public array $images = [];

    public string $error = '';

    public function mount(VendorApi $vendorApi, ?string $product = null): void
    {
        $this->categories = $vendorApi->categories();

        if ($product) {
            $this->productId = $product;
            $data = $vendorApi->product($product);
            $this->name = $data['name'];
            $this->sku = $data['sku'] ?? '';
            $this->category_id = $data['category']['id'] ?? '';
            $this->description = $data['description'] ?? '';
            $this->price_ghs = (string) $data['price_ghs'];
            $this->discount_price_ghs = (string) ($data['discount_price_ghs'] ?? '');
            $this->stock = (string) $data['stock'];
            $this->is_active = (bool) $data['is_active'];
            $this->images = $data['images'] ?? [];
        }
    }

    public function save(VendorApi $vendorApi): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'price_ghs' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $payload = [
            'name' => $this->name,
            'sku' => $this->sku ?: null,
            'category_id' => $this->category_id ?: null,
            'description' => $this->description,
            'price_ghs' => (float) $this->price_ghs,
            'discount_price_ghs' => $this->discount_price_ghs !== '' ? (float) $this->discount_price_ghs : null,
            'stock' => (int) $this->stock,
            'is_active' => $this->is_active,
        ];

        try {
            $product = $this->productId
                ? $vendorApi->updateProduct($this->productId, $payload)
                : $vendorApi->createProduct($payload);

            $this->productId = $product['id'];

            $this->dispatch('toast', type: 'success', message: 'Product saved.');
            $this->redirect(route('vendor.products.index'), navigate: false);
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function uploadImage(KasabazaarClient $client): void
    {
        if (! $this->productId || ! $this->newImage) {
            return;
        }

        $client->postMultipart("marketplace/vendor/products/{$this->productId}/images", [], [
            ['name' => 'image', 'contents' => fopen($this->newImage->getRealPath(), 'r'), 'filename' => $this->newImage->getClientOriginalName()],
        ]);

        $this->images = app(VendorApi::class)->product($this->productId)['images'] ?? [];
        $this->newImage = null;
        $this->dispatch('toast', type: 'success', message: 'Image uploaded.');
    }

    public function render()
    {
        return view('livewire.vendor.products.form')->layout('vendor.layouts.dashboard', [
            'title' => $this->productId ? 'Edit Product' : 'Add Product',
        ]);
    }
}
