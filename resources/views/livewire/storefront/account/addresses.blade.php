<div class="container py-8">
    <div class="row">
        @include('storefront.account.nav')

        <div class="col-md-9">
            <h1 class="title title-simple mb-4">My Addresses</h1>

            <div class="row">
                @foreach ($addresses as $address)
                    <div class="col-md-6 mb-4">
                        <div class="border rounded p-3">
                            @if ($address['is_default'])
                                <span class="badge badge-dark mb-2">Default</span>
                            @endif
                            <p class="mb-1"><strong>{{ $address['full_name'] }}</strong> — {{ $address['phone'] }}</p>
                            <p class="mb-2">{{ $address['street'] }}, {{ $address['city'] }}, {{ $address['region'] }}</p>
                            @if (!$address['is_default'])
                                <button type="button" class="btn btn-link p-0 mr-3" wire:click="setDefault('{{ $address['id'] }}')">Set as default</button>
                            @endif
                            <button type="button" class="btn btn-link p-0 text-danger" wire:click="delete('{{ $address['id'] }}')" wire:confirm="Delete this address?">Delete</button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($showForm)
                <div class="border rounded p-3 mt-4" style="max-width:480px;">
                    <div class="form-group"><input class="form-control" placeholder="Full name" wire:model="full_name"></div>
                    <div class="form-group"><input class="form-control" placeholder="Phone" wire:model="phone"></div>
                    <div class="form-group"><input class="form-control" placeholder="Region" wire:model="region"></div>
                    <div class="form-group"><input class="form-control" placeholder="City" wire:model="city"></div>
                    <div class="form-group"><input class="form-control" placeholder="Street / landmark" wire:model="street"></div>
                    <button type="button" class="btn btn-dark" wire:click="save">Save Address</button>
                </div>
            @else
                <button type="button" class="btn btn-outline-dark mt-4" wire:click="$set('showForm', true)">+ Add New Address</button>
            @endif
        </div>
    </div>
</div>
