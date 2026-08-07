<?php

namespace App\Livewire\Vendor\Reviews;

use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public array $replyDrafts = [];

    public function reply(VendorApi $vendorApi, string $reviewId): void
    {
        $vendorApi->replyToReview($reviewId, $this->replyDrafts[$reviewId] ?? '');
        $this->dispatch('toast', type: 'success', message: 'Reply posted.');
    }

    public function render(VendorApi $vendorApi)
    {
        $response = $vendorApi->reviews(['page' => $this->getPage(), 'per_page' => 20]);

        return view('livewire.vendor.reviews.index', [
            'reviews' => $response->data,
            'meta' => $response->meta,
        ])->layout('vendor.layouts.dashboard', ['title' => 'Reviews']);
    }
}
