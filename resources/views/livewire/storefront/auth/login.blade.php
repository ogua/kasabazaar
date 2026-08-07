<div class="container py-8" style="max-width:480px;">
    <h1 class="title title-simple mb-6">Sign In</h1>

    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form wire:submit.prevent="submit">
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" wire:model="email" required autofocus>
            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" class="form-control" wire:model="password" required>
            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="btn btn-dark btn-block" wire:loading.attr="disabled">Sign In</button>
    </form>

    <p class="mt-4">
        <a href="{{ route('storefront.password.request') }}">Forgot your password?</a>
    </p>
    <p>Don't have an account? <a href="{{ route('storefront.register') }}">Register</a></p>
</div>
