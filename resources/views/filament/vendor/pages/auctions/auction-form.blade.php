<x-filament-panels::page>
    @isset($lot)
        @if (! empty($lot))
            <x-filament::section>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <x-filament::badge :color="match (true) {
                        $lot['status'] === 'pending_review' => 'warning',
                        $lot['status'] === 'draft' && filled($lot['review_notes']) => 'danger',
                        $lot['state'] === 'live' => 'success',
                        default => 'gray',
                    }">
                        {{ \App\Filament\Vendor\Pages\Auctions\ListAuctions::statusLabel($lot) }}
                    </x-filament::badge>
                    @if ($lot['bids_count'] > 0)
                        <span>{{ $lot['bids_count'] }} {{ \Illuminate\Support\Str::plural('bid', $lot['bids_count']) }} · current GHS {{ number_format((float) $lot['current_price_ghs'], 2) }}</span>
                    @endif
                    @if (in_array($lot['state'], ['live', 'upcoming', 'ended'], true))
                        <a href="{{ route('storefront.auction', $lot['slug']) }}" target="_blank" class="text-primary-600 underline">View on the storefront</a>
                    @endif
                </div>
                @if (filled($lot['review_notes'] ?? null))
                    <p class="mt-3 text-sm text-danger-600"><strong>Changes requested:</strong> {{ $lot['review_notes'] }}</p>
                @endif
                @unless ($this->isEditable())
                    <p class="mt-3 text-sm text-gray-500">This auction is published, so it can no longer be edited. Contact us if something needs to change.</p>
                @endunless
            </x-filament::section>
        @endif
    @endisset

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        @if (! isset($lot) || $this->isEditable())
            <x-filament::button type="submit">
                {{ isset($lot) ? 'Save auction' : 'Save draft' }}
            </x-filament::button>
        @endif
    </form>

    @isset($lot)
        <x-filament::section heading="Photos">
            <div class="flex flex-wrap gap-3">
                @forelse ($lot['images'] ?? [] as $image)
                    <div class="relative" wire:key="auction-image-{{ $image['index'] }}-{{ md5($image['url']) }}">
                        <img src="{{ $image['url'] }}" alt="" class="h-24 w-24 rounded object-cover">
                        @if ($loop->first)
                            <span class="absolute bottom-1 left-1 rounded bg-black/60 px-1 text-[10px] text-white">Cover</span>
                        @endif
                        @if ($this->isEditable())
                            <button type="button" wire:click="removeImage({{ $image['index'] }})" wire:confirm="Remove this photo?" class="absolute -right-2 -top-2 rounded-full bg-danger-600 p-1 text-white" aria-label="Remove photo">
                                <x-filament::icon icon="heroicon-m-x-mark" class="h-3 w-3" />
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No photos yet — add at least one before submitting for review.</p>
                @endforelse
            </div>
        </x-filament::section>

        @if (count($bids))
            <x-filament::section heading="Bids">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2">Bidder</th>
                            <th class="py-2">Amount</th>
                            <th class="py-2">Placed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bids as $bid)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="py-2">{{ $bid['bidder'] }}</td>
                                <td class="py-2 font-semibold">GHS {{ number_format($bid['amount_ghs'], 2) }}</td>
                                <td class="py-2">{{ \Illuminate\Support\Carbon::parse($bid['placed_at'])->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
        @endif
    @endisset
</x-filament-panels::page>
