<x-storefront.account-layout title="Account Details">
    <div class="grid lg:grid-cols-2 gap-8">
        <x-storefront.ui.card>
            <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Profile</h2>

            @if ($profileMessage)
                <x-storefront.ui.alert variant="success" class="mb-4">{{ $profileMessage }}</x-storefront.ui.alert>
            @endif
            @if ($profileError)
                <x-storefront.ui.alert variant="error" class="mb-4">{{ $profileError }}</x-storefront.ui.alert>
            @endif

            <form wire:submit.prevent="updateProfile" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-fg mb-1.5">Full Name</label>
                    <input type="text" wire:model="name" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                    @error('name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-fg mb-1.5">Phone</label>
                    <input type="text" wire:model="phone" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                </div>
                <x-storefront.ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updateProfile">
                    <span wire:loading.remove wire:target="updateProfile">Save Changes</span>
                    <span wire:loading wire:target="updateProfile" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Saving...</span>
                </x-storefront.ui.button>
            </form>
        </x-storefront.ui.card>

        <x-storefront.ui.card>
            <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Change Password</h2>

            @if ($passwordMessage)
                <x-storefront.ui.alert variant="success" class="mb-4">{{ $passwordMessage }}</x-storefront.ui.alert>
            @endif
            @if ($passwordError)
                <x-storefront.ui.alert variant="error" class="mb-4">{{ $passwordError }}</x-storefront.ui.alert>
            @endif

            <form wire:submit.prevent="updatePassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-fg mb-1.5">Current Password</label>
                    <input type="password" wire:model="current_password" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                    @error('current_password') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-fg mb-1.5">New Password</label>
                    <input type="password" wire:model="new_password" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                    @error('new_password') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-fg mb-1.5">Confirm New Password</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                </div>
                <x-storefront.ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updatePassword">
                    <span wire:loading.remove wire:target="updatePassword">Change Password</span>
                    <span wire:loading wire:target="updatePassword" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Changing...</span>
                </x-storefront.ui.button>
            </form>
        </x-storefront.ui.card>
    </div>
</x-storefront.account-layout>
