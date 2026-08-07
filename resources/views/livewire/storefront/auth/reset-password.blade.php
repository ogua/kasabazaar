<div class="container py-8" style="max-width:480px;">
    <h1 class="title title-simple mb-6">Reset Password</h1>

    @if ($done)
        <div class="alert alert-success">Password reset successfully. <a href="{{ route('storefront.login') }}">Sign in</a>.</div>
    @else
        @if ($error)
            <div class="alert alert-danger">{{ $error }}</div>
        @endif
        <form wire:submit.prevent="submit">
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" wire:model="email" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" class="form-control" wire:model="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" class="form-control" wire:model="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Reset Password</button>
        </form>
    @endif
</div>
