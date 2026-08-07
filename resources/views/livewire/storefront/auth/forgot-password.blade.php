<div class="container py-8" style="max-width:480px;">
    <h1 class="title title-simple mb-6">Forgot Password</h1>

    @if ($sent)
        <div class="alert alert-success">If an account with that email exists, a reset link has been sent.</div>
    @else
        <form wire:submit.prevent="submit">
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" wire:model="email" required autofocus>
                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn btn-dark btn-block">Send Reset Link</button>
        </form>
    @endif
</div>
