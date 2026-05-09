<div>
    @if($submitted)
        <div class="text-center py-4">
            <div class="mb-3">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
            </div>
            <h4 class="text-success mb-2">Request Sent!</h4>
            <p class="text-muted mb-4">Thank you for choosing Rose Door to Door. We'll get back to you with a personalized quote shortly.</p>
            <button wire:click="submitAnother" class="btn btn-outline-secondary btn-sm">
                Submit Another Request
            </button>
        </div>
    @else
        <form wire:submit="submit">
            <h3>Get Your Personalized Quote</h3>
            <p>Ready to ship? Fill out the form below to receive a customized quote tailored to your shipping needs.</p>
            <div class="row gy-3">

                <div class="col-12">
                    <input type="text" wire:model="name"
                        class="form-control @error('name') is-invalid @enderror"
                        placeholder="Your Name" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <input type="email" wire:model="email"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="Your Email" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <input type="text" wire:model="phone"
                        class="form-control"
                        placeholder="Your Phone (Optional)">
                </div>

                <div class="col-12">
                    <textarea wire:model="message"
                        class="form-control @error('message') is-invalid @enderror"
                        rows="5" placeholder="Tell us about your shipment" required></textarea>
                    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="text-center col-12">
                    <button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Get a Quote</span>
                        <span wire:loading wire:target="submit" style="display:none;">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>Sending...
                        </span>
                    </button>
                </div>

            </div>
        </form>
    @endif
</div>
