<x-storefront.account-layout title="My Addresses">
    @if ($error)
        <x-storefront.ui.alert variant="error" class="mb-6">{{ $error }}</x-storefront.ui.alert>
    @endif

    <div class="grid sm:grid-cols-2 gap-4 mb-6" wire:loading.class="opacity-50" wire:target="delete,setDefault">
        @foreach ($addresses as $address)
            <x-storefront.ui.card wire:key="address-{{ $address['id'] }}">
                @if ($address['is_default'])
                    <x-storefront.ui.badge variant="navy" class="mb-2">Default</x-storefront.ui.badge>
                @endif
                <p class="text-sm font-semibold text-fg">{{ $address['full_name'] }} &middot; {{ $address['phone'] }}</p>
                <p class="text-sm text-muted mt-1 mb-3">{{ $address['street'] }}, {{ $address['city'] }}, {{ $address['region'] }}</p>
                <div class="flex items-center gap-4 text-sm">
                    @if (! $address['is_default'])
                        <button type="button" wire:click="setDefault('{{ $address['id'] }}')" class="text-navy-500 font-medium hover:text-accent">Set as default</button>
                    @endif
                    <button type="button" wire:click="delete('{{ $address['id'] }}')" wire:confirm="Delete this address?" class="text-error font-medium hover:underline">Delete</button>
                </div>
            </x-storefront.ui.card>
        @endforeach
    </div>

    @if ($showForm)
        <x-storefront.ui.card class="max-w-lg">
            <h2 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">New Address</h2>
            <div class="space-y-3 mb-4">
                <input class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Full name" wire:model="full_name">
                @error('full_name') <p class="text-error text-xs">{{ $message }}</p> @enderror
                <input class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Phone" wire:model="phone">
                @error('phone') <p class="text-error text-xs">{{ $message }}</p> @enderror
                <input class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Region" wire:model="region">
                @error('region') <p class="text-error text-xs">{{ $message }}</p> @enderror
                <input class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="City" wire:model="city">
                @error('city') <p class="text-error text-xs">{{ $message }}</p> @enderror
                <input class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Street / landmark" wire:model="street">
            </div>
            <x-storefront.ui.button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save Address</span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Saving...</span>
            </x-storefront.ui.button>
        </x-storefront.ui.card>
    @else
        <button type="button" wire:click="$set('showForm', true)" class="text-sm font-medium text-navy-500 hover:text-accent">
            + Add New Address
        </button>
    @endif
</x-storefront.account-layout>
