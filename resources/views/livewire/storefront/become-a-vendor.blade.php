<div class="container py-8" style="max-width:640px;">
    <h1 class="title title-simple mb-2">Become a Vendor</h1>
    <p class="mb-6">Join {{ config('app.name') }} and start selling to shoppers across Ghana. Applications are reviewed within 2–3 business days.</p>

    @if ($submitted)
        <div class="alert alert-success">Thanks! Your application has been submitted. We'll email you once it's reviewed.</div>
    @else
        @if ($error)
            <div class="alert alert-danger">{{ $error }}</div>
        @endif

        <form wire:submit.prevent="submit">
            <div class="form-group">
                <label>Business Name</label>
                <input type="text" class="form-control" wire:model="business_name">
                @error('business_name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Contact Name</label>
                <input type="text" class="form-control" wire:model="contact_name">
                @error('contact_name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" wire:model="email">
                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" class="form-control" wire:model="phone">
            </div>
            <div class="form-group">
                <label>What will you sell?</label>
                <input type="text" class="form-control" wire:model="product_category" placeholder="e.g. Fashion, Electronics">
            </div>
            <div class="form-group">
                <label>Tell us about your business</label>
                <textarea class="form-control" wire:model="message" rows="3"></textarea>
            </div>

            <h4 class="mt-6">Verification Documents</h4>
            <div class="form-group">
                <label>Business Certificate</label>
                <input type="file" class="form-control" wire:model="business_certificate">
                @error('business_certificate') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Ghana Card (Front)</label>
                <input type="file" class="form-control" wire:model="ghana_card_front">
                @error('ghana_card_front') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Ghana Card (Back)</label>
                <input type="file" class="form-control" wire:model="ghana_card_back">
                @error('ghana_card_back') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Proof of Address (optional)</label>
                <input type="file" class="form-control" wire:model="proof_of_address">
            </div>

            <button type="submit" class="btn btn-dark btn-block mt-4" wire:loading.attr="disabled">Submit Application</button>
        </form>
    @endif
</div>
