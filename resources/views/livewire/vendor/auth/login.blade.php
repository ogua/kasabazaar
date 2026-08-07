<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Sign In — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('storefront/assets/css/demo2.min.css') }}">
    @livewireStyles
</head>
<body>
    <div class="container py-8" style="max-width:420px;margin:0 auto;">
        <h1 class="title title-simple mb-6">Vendor Sign In</h1>

        @if ($error)
            <div class="alert alert-danger">{{ $error }}</div>
        @endif

        <form wire:submit.prevent="submit">
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" wire:model="email" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" wire:model="password" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Sign In</button>
        </form>

        <p class="mt-4"><a href="{{ route('storefront.become-vendor') }}">Want to sell here? Apply to become a vendor.</a></p>
    </div>
    @livewireScripts
</body>
</html>
