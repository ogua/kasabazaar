<div>
    @if($submitted)
        <!-- Success Message -->
        <div class="text-center py-5" data-aos="fade-up">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            </div>
            <h3 class="text-success mb-3">Thank You for Your Feedback!</h3>
            <p class="text-muted mb-4">
                Your feedback has been submitted successfully. We appreciate you taking the time to share your experience with us.
                Our team will review your feedback and may reach out if we need additional information.
            </p>
            <button wire:click="submitAnother" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Submit Another Feedback
            </button>
        </div>
    @else
        <!-- Feedback Form -->
        <form wire:submit="submit" class="php-email-form" data-aos="fade-up" data-aos-delay="400">
            <div class="row gy-4">

                <!-- Service Selection -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Service <span class="text-danger">*</span></label>
                    <select wire:model="feedback_on" class="form-control @error('feedback_on') is-invalid @enderror" required>
                        @foreach($feedbackSources as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('feedback_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Invoice Number -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Invoice/Reference Number (Optional)</label>
                    <input type="text" wire:model="invoice_number" class="form-control" placeholder="Enter invoice or reference number">
                </div>

                <!-- Customer Name -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Your Name <span class="text-danger">*</span></label>
                    <input type="text" wire:model="customer_name" class="form-control @error('customer_name') is-invalid @enderror" placeholder="Enter your full name" required>
                    @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Customer Email -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Your Email</label>
                    <input type="email" wire:model="customer_email" class="form-control @error('customer_email') is-invalid @enderror" placeholder="Enter your email address" required>
                    @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Customer Phone -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Your Phone <span class="text-danger">*</span></label>
                    <input type="text" wire:model="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" placeholder="Enter your phone number" required>
                    @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Feedback Category -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Feedback Category <span class="text-danger">*</span></label>
                    <select wire:model="category" class="form-control @error('category') is-invalid @enderror" required>
                        @foreach($categories as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Rating -->
                <div class="col-md-12">
                    <label class="form-label fw-bold">How would you rate your experience? <span class="text-danger">*</span></label>
                    <div class="d-flex gap-3 flex-wrap">
                        @foreach([1 => 'Very Poor', 2 => 'Poor', 3 => 'Average', 4 => 'Good', 5 => 'Excellent'] as $value => $label)
                            <div class="form-check">
                                <input
                                    type="radio"
                                    wire:model="rating"
                                    value="{{ $value }}"
                                    id="rating{{ $value }}"
                                    class="form-check-input"
                                    {{ $rating == $value ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="rating{{ $value }}">
                                    <span class="text-warning">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $value)
                                                <i class="bi bi-star-fill"></i>
                                            @else
                                                <i class="bi bi-star"></i>
                                            @endif
                                        @endfor
                                    </span>
                                    <small class="d-block text-muted">{{ $label }}</small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('rating') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <!-- Comment -->
                <div class="col-md-12">
                    <label class="form-label fw-bold">Your Comments (Optional)</label>
                    <textarea wire:model="comment" class="form-control" rows="5" placeholder="Please share your feedback, suggestions, or concerns..."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="col-md-12 text-center">
                    <button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Submit Feedback</span>
                        <span wire:loading wire:target="submit" style="display: none;">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Submitting...
                        </span>
                    </button>
                </div>

            </div>
        </form>
    @endif
</div>
