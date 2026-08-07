<div class="stat-card" style="max-width:640px;">
    <h4>Branding</h4>
    <div class="mb-4">
        <label class="d-block">Logo</label>
        @if ($logo_url) <img src="{{ $logo_url }}" width="80" height="80" class="rounded-circle mb-2"> @endif
        <input type="file" wire:model="newLogo">
        @if ($newLogo) <button type="button" class="btn btn-sm btn-outline-dark ml-2" wire:click="uploadLogo">Upload</button> @endif
    </div>
    <div class="mb-4">
        <label class="d-block">Banner</label>
        @if ($banner_url) <img src="{{ $banner_url }}" width="240" height="80" class="mb-2" style="object-fit:cover;"> @endif
        <input type="file" wire:model="newBanner">
        @if ($newBanner) <button type="button" class="btn btn-sm btn-outline-dark ml-2" wire:click="uploadBanner">Upload</button> @endif
    </div>

    <hr>

    <form wire:submit.prevent="save">
        <div class="form-group">
            <label>Business Name</label>
            <input type="text" class="form-control" wire:model="business_name">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" wire:model="description" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" class="form-control" wire:model="phone">
        </div>
        <div class="form-group">
            <label>Support Email</label>
            <input type="email" class="form-control" wire:model="support_email">
        </div>
        <div class="form-group">
            <label>Payout Method</label>
            <select class="form-control" wire:model="payout_method">
                <option value="">— Select —</option>
                <option value="momo">Mobile Money</option>
                <option value="bank">Bank Transfer</option>
            </select>
        </div>
        <div class="form-group">
            <label>Payout Details (account number, etc.)</label>
            <textarea class="form-control" wire:model="payout_details" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-dark">Save Settings</button>
    </form>
</div>
