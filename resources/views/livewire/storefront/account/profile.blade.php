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
                    <label for="profile-name" class="block text-sm font-medium text-fg mb-1.5">Full Name</label>
                    <input id="profile-name" autocomplete="name" type="text"  class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="name" @error('name') aria-invalid="true" aria-describedby="profile-name-error" @enderror>
                    @error('name') <p id="profile-name-error" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="profile-phone" class="block text-sm font-medium text-fg mb-1.5">Phone</label>
                    <input id="profile-phone" autocomplete="tel" type="tel"  class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="phone" @error('phone') aria-invalid="true" aria-describedby="profile-phone-error" @enderror>
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
                    <label for="profile-current-password" class="block text-sm font-medium text-fg mb-1.5">Current Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="profile-current-password" autocomplete="current-password" type="password" :type="show ? 'text' : 'password'"  class="w-full border border-border rounded-sm pl-3 pr-11 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="current_password" @error('current_password') aria-invalid="true" aria-describedby="profile-current-password-error" @enderror>
                        <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-muted hover:text-navy-900" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show">
                            <x-storefront.icon name="eye" class="w-5 h-5" x-show="! show" />
                            <x-storefront.icon name="eye-slash" class="w-5 h-5" x-show="show" x-cloak />
                        </button>
                    </div>
                    @error('current_password') <p id="profile-current-password-error" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="profile-new-password" class="block text-sm font-medium text-fg mb-1.5">New Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="profile-new-password" autocomplete="new-password" type="password" :type="show ? 'text' : 'password'"  class="w-full border border-border rounded-sm pl-3 pr-11 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="new_password" @error('new_password') aria-invalid="true" aria-describedby="profile-new-password-error" @enderror>
                        <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-muted hover:text-navy-900" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show">
                            <x-storefront.icon name="eye" class="w-5 h-5" x-show="! show" />
                            <x-storefront.icon name="eye-slash" class="w-5 h-5" x-show="show" x-cloak />
                        </button>
                    </div>
                    @error('new_password') <p id="profile-new-password-error" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="profile-new-password-confirmation" class="block text-sm font-medium text-fg mb-1.5">Confirm New Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="profile-new-password-confirmation" autocomplete="new-password" type="password" :type="show ? 'text' : 'password'"  class="w-full border border-border rounded-sm pl-3 pr-11 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="new_password_confirmation" @error('new_password_confirmation') aria-invalid="true" aria-describedby="profile-new-password-confirmation-error" @enderror>
                        <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-muted hover:text-navy-900" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show">
                            <x-storefront.icon name="eye" class="w-5 h-5" x-show="! show" />
                            <x-storefront.icon name="eye-slash" class="w-5 h-5" x-show="show" x-cloak />
                        </button>
                    </div>
                </div>
                <x-storefront.ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updatePassword">
                    <span wire:loading.remove wire:target="updatePassword">Change Password</span>
                    <span wire:loading wire:target="updatePassword" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Changing...</span>
                </x-storefront.ui.button>
            </form>
        </x-storefront.ui.card>
    </div>
</x-storefront.account-layout>
