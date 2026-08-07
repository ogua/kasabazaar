<div class="container py-8">
    <div class="row">
        @include('storefront.account.nav')

        <div class="col-md-9">
            <h1 class="title title-simple mb-4">Account Details</h1>

            @if ($profileMessage)
                <div class="alert alert-success">{{ $profileMessage }}</div>
            @endif

            <form wire:submit.prevent="updateProfile" style="max-width:480px;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" wire:model="name">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" wire:model="phone">
                </div>
                <button type="submit" class="btn btn-dark">Save Changes</button>
            </form>

            <h3 class="mt-8">Change Password</h3>
            @if ($passwordMessage)
                <div class="alert alert-success">{{ $passwordMessage }}</div>
            @endif
            @if ($passwordError)
                <div class="alert alert-danger">{{ $passwordError }}</div>
            @endif
            <form wire:submit.prevent="updatePassword" style="max-width:480px;">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" class="form-control" wire:model="current_password">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" class="form-control" wire:model="new_password">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" class="form-control" wire:model="new_password_confirmation">
                </div>
                <button type="submit" class="btn btn-dark">Change Password</button>
            </form>
        </div>
    </div>
</div>
