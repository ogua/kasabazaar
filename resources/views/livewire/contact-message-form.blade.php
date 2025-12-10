<div>
    @if($submitted)
        <!-- Success Message -->
        <div class="text-center py-4" data-aos="fade-up">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            </div>
            <h3 class="text-success mb-3">Message Sent Successfully!</h3>
            <p class="text-muted mb-4">
                Thank you for contacting us. We have received your message and will get back to you as soon as possible.
            </p>
            <button wire:click="submitAnother" class="btn btn-primary">
                <i class="bi bi-envelope me-2"></i>Send Another Message
            </button>
        </div>
    @else
        <!-- Contact Form -->
        <form wire:submit="submit" data-aos="fade-up" data-aos-delay="400">
            <div class="row gy-4">

                <div class="col-md-6">
                    <label class="form-label fw-bold">Your Name <span class="text-danger">*</span></label>
                    <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="Enter your full name" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Your Email</label>
                    <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="Enter your email address" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Phone Number (Optional)</label>
                    <input type="text" wire:model="phone" class="form-control" placeholder="Enter your phone number">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                    <input type="text" wire:model="subject" class="form-control @error('subject') is-invalid @enderror" placeholder="Enter message subject" required>
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                    <textarea wire:model="message" class="form-control @error('message') is-invalid @enderror" rows="6" placeholder="Enter your message" required></textarea>
                    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">
                            <i class="bi bi-send me-2"></i>Send Message
                        </span>
                        <span wire:loading wire:target="submit" style="display: none;">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Sending...
                        </span>
                    </button>
                </div>

            </div>
        </form>
    @endif
</div>
