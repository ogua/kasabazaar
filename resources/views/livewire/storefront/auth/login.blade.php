<div class="max-w-md mx-auto px-4 sm:px-6 py-16">
    <div class="text-center mb-8">
        <img src="{{ asset('images/brand/logo-stacked.png') }}" alt="{{ config('app.name') }}" class="h-16 w-auto mx-auto mb-5">
        <h1 class="font-display font-bold text-2xl text-navy-900">Sign In</h1>
        <p class="text-muted text-sm mt-1">Welcome back to {{ config('app.name') }}</p>
    </div>

    <x-storefront.ui.card>
        @if ($error)
            <x-storefront.ui.alert variant="error" class="mb-5">{{ $error }}</x-storefront.ui.alert>
        @endif

        <form wire:submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-fg mb-1.5">Email</label>
                <input type="email" wire:model="email" required autofocus class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                @error('email') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-fg mb-1.5">Password</label>
                <input type="password" wire:model="password" required class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                @error('password') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <x-storefront.ui.button type="submit" variant="primary" size="lg" wire:loading.attr="disabled" wire:target="submit" class="w-full">
                <span wire:loading.remove wire:target="submit">Sign In</span>
                <span wire:loading wire:target="submit" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Signing in...</span>
            </x-storefront.ui.button>
        </form>

        <p class="text-center mt-4">
            <a href="{{ route('storefront.password.request') }}" class="text-sm text-navy-500 hover:text-accent">Forgot your password?</a>
        </p>
    </x-storefront.ui.card>

    <p class="text-center text-sm text-muted mt-6">
        Don't have an account? <a href="{{ route('storefront.register') }}" class="text-navy-900 font-semibold hover:text-accent">Register</a>
    </p>
</div>
