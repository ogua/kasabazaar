<?php

namespace App\Livewire\Storefront\Shared;

use App\Support\CompareList;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Floating "compare" bar, mounted once in the layout. Product cards and the
 * product page dispatch `toggle-compare`; the tray owns the session list and
 * broadcasts `compare-updated` so every compare button can reflect it.
 */
class CompareTray extends Component
{
    #[On('toggle-compare')]
    public function toggle(CompareList $compare, string $productId, string $name = '', ?string $image = null): void
    {
        if ($compare->has($productId)) {
            $compare->remove($productId);
            $this->dispatch('toast', type: 'success', message: 'Removed from comparison.');
        } elseif (! $compare->add($productId, $name ?: 'Product', $image)) {
            $this->dispatch('toast', type: 'error', message: 'You can compare up to '.CompareList::MAX_ITEMS.' products. Remove one first.');
        } else {
            $this->dispatch('toast', type: 'success', message: 'Added to comparison.');
        }

        $this->dispatch('compare-updated', ids: $compare->ids());
    }

    public function remove(CompareList $compare, string $productId): void
    {
        $compare->remove($productId);
        $this->dispatch('compare-updated', ids: $compare->ids());
    }

    public function clear(CompareList $compare): void
    {
        $compare->clear();
        $this->dispatch('compare-updated', ids: []);
    }

    public function render(CompareList $compare)
    {
        return view('livewire.storefront.shared.compare-tray', [
            'items' => array_values($compare->all()),
            'max' => CompareList::MAX_ITEMS,
        ]);
    }
}
