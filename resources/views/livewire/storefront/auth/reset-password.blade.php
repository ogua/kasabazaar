<div class="max-w-md mx-auto px-4 sm:px-6 py-16">
    <div class="text-center mb-8">
        <img src="{{ asset('images/brand/logo-stacked.png') }}" alt="{{ config('app.name') }}" class="h-16 w-auto mx-auto mb-5">
        <h1 class="font-display font-bold text-2xl text-navy-900">Reset Password</h1>
    </div>

    <x-storefront.ui.card>
        @if ($done)
            <x-storefront.ui.alert variant="success" class="mb-4">Password reset successfully.</x-storefront.ui.alert>
            <x-storefront.ui.button href="{{ route('storefront.login') }}" variant="primary" class="w-full">Sign In</x-storefront.ui.button>
        @else
            @if ($error)
                <x-storefront.ui.alert variant="error" class="mb-5">{{ $error }}</x-storefront.ui.alert>
            @endif

            <form wire:submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="reset-email" class="block text-sm font-medium text-fg mb-1.5">Email</label>
                    <input id="reset-email" autocomplete="email" type="email"  required class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="email" @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror>
                    @error('email') <p id="reset-email-error" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reset-password" class="block text-sm font-medium text-fg mb-1.5">New Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="reset-password" autocomplete="new-password" type="password" :type="show ? 'text' : 'password'"  required class="w-full border border-border rounded-sm pl-3 pr-11 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="password" @error('password') aria-invalid="true" aria-describedby="reset-password-error" @enderror>
                        <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-muted hover:text-navy-900" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show">
                            <x-storefront.icon name="eye" class="w-5 h-5" x-show="! show" />
                            <x-storefront.icon name="eye-slash" class="w-5 h-5" x-show="show" x-cloak />
                        </button>
                    </div>
                    @error('password') <p id="reset-password-error" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reset-password-confirmation" class="block text-sm font-medium text-fg mb-1.5">Confirm Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="reset-password-confirmation" autocomplete="new-password" type="password" :type="show ? 'text' : 'password'"  required class="w-full border border-border rounded-sm pl-3 pr-11 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" wire:model="password_confirmation" @error('password_confirmation') aria-invalid="true" aria-describedby="reset-password-confirmation-error" @enderror>
                        <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-muted hover:text-navy-900" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show">
                            <x-storefront.icon name="eye" class="w-5 h-5" x-show="! show" />
                            <x-storefront.icon name="eye-slash" class="w-5 h-5" x-show="show" x-cloak />
                        </button>
                    </div>
                </div>

                <x-storefront.ui.button type="submit" variant="primary" size="lg" wire:loading.attr="disabled" wire:target="submit" class="w-full">
                    <span wire:loading.remove wire:target="submit">Reset Password</span>
                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Resetting...</span>
                </x-storefront.ui.button>
            </form>
        @endif
    </x-storefront.ui.card>
</div>
