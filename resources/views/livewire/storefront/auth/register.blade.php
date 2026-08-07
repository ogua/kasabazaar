<div class="container py-8" style="max-width:480px;">
    <h1 class="title title-simple mb-6">Create Account</h1>

    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form wire:submit.prevent="submit">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" class="form-control" wire:model="name" required autofocus>
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" wire:model="email" required>
            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" class="form-control" wire:model="phone">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" class="form-control" wire:model="password" required>
            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" class="form-control" wire:model="password_confirmation" required>
        </div>
        <button type="submit" class="btn btn-dark btn-block" wire:loading.attr="disabled">Create Account</button>
    </form>

    <p class="mt-4">Already have an account? <a href="{{ route('storefront.login') }}">Sign In</a></p>
</div>
