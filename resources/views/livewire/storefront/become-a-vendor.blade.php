<div class="max-w-2xl mx-auto px-4 sm:px-6 py-12">
    <div class="text-center mb-10">
        <h1 class="font-display font-bold text-3xl text-navy-900 mb-3">Become a Vendor</h1>
        <p class="text-muted">Join {{ config('app.name') }} and start selling to shoppers across Ghana. Applications are reviewed within 2&ndash;3 business days.</p>
    </div>

    @if ($submitted)
        <x-storefront.ui.empty-state icon="check-circle" title="Application submitted" description="Thanks! We'll email you once it's reviewed.">
            <x-slot:action>
                <x-storefront.ui.button href="{{ route('storefront.home') }}" variant="primary">Back to Home</x-storefront.ui.button>
            </x-slot:action>
        </x-storefront.ui.empty-state>
    @else
        @if ($error)
            <x-storefront.ui.alert variant="error" class="mb-6">{{ $error }}</x-storefront.ui.alert>
        @endif

        <x-storefront.ui.card>
            <form wire:submit.prevent="submit" class="space-y-5">
                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Business Name</label>
                        <input type="text" wire:model="business_name" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        @error('business_name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Contact Name</label>
                        <input type="text" wire:model="contact_name" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        @error('contact_name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Email</label>
                        <input type="email" wire:model="email" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        @error('email') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Phone</label>
                        <input type="text" wire:model="phone" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-fg mb-1.5">What will you sell?</label>
                    <input type="text" wire:model="product_category" placeholder="e.g. Fashion, Electronics" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-fg mb-1.5">Tell us about your business</label>
                    <textarea wire:model="message" rows="3" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"></textarea>
                </div>

                <div class="border-t border-border pt-5">
                    <h2 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">Verification Documents</h2>

                    <div class="grid sm:grid-cols-2 gap-5">
                        @foreach ([
                            ['field' => 'business_certificate', 'label' => 'Business Certificate'],
                            ['field' => 'ghana_card_front', 'label' => 'Ghana Card (Front)'],
                            ['field' => 'ghana_card_back', 'label' => 'Ghana Card (Back)'],
                            ['field' => 'proof_of_address', 'label' => 'Proof of Address (optional)'],
                        ] as $upload)
                            <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-border rounded-lg p-4 text-center cursor-pointer hover:border-navy-500 transition-colors">
                                <x-storefront.icon name="upload" class="w-5 h-5 text-muted" />
                                <span class="text-sm font-medium text-fg">{{ $upload['label'] }}</span>
                                <span class="text-xs text-muted" wire:loading.remove wire:target="{{ $upload['field'] }}">
                                    {{ $this->{$upload['field']} ? $this->{$upload['field']}->getClientOriginalName() : 'Click to upload' }}
                                </span>
                                <span class="text-xs text-muted inline-flex items-center gap-1.5" wire:loading wire:target="{{ $upload['field'] }}">
                                    <x-storefront.ui.spinner class="w-3.5 h-3.5" /> Uploading...
                                </span>
                                <input type="file" wire:model="{{ $upload['field'] }}" class="hidden">
                                @error($upload['field']) <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </label>
                        @endforeach
                    </div>
                </div>

                <x-storefront.ui.button type="submit" variant="accent" size="lg" wire:loading.attr="disabled" wire:target="submit" class="w-full">
                    <span wire:loading.remove wire:target="submit">Submit Application</span>
                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Submitting...</span>
                </x-storefront.ui.button>
            </form>
        </x-storefront.ui.card>
    @endif
</div>
