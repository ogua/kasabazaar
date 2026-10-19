{{-- Delivery address fields shared by checkout, auction payment and the account
     address book. Binds full_name, phone, region, city and street on the parent
     Livewire component. `prefix` keeps ids unique when two forms share a page. --}}
@props(['prefix' => 'address'])

@php
    $inputClass = 'w-full border border-border rounded-sm px-3 py-2.5 text-sm bg-surface focus:outline-none focus-visible:outline-2 focus-visible:outline-accent';
    $fields = [
        'full_name' => ['label' => 'Full name', 'autocomplete' => 'name'],
        'phone' => ['label' => 'Phone', 'autocomplete' => 'tel', 'type' => 'tel', 'placeholder' => 'e.g. 024 123 4567'],
        'region' => ['label' => 'Region', 'autocomplete' => 'address-level1'],
        'city' => ['label' => 'City or town', 'autocomplete' => 'address-level2'],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'grid sm:grid-cols-2 gap-4']) }}>
    @foreach ($fields as $name => $field)
        @php $id = "{$prefix}-".str_replace('_', '-', $name); @endphp
        <div>
            <label for="{{ $id }}" class="block text-sm font-medium text-fg mb-1.5">{{ $field['label'] }}</label>
            @if ($name === 'region')
                {{-- A fixed list: the backend matches shipping zones on the exact region name. --}}
                <select id="{{ $id }}" wire:model="region" autocomplete="{{ $field['autocomplete'] }}" class="{{ $inputClass }}" @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
                    <option value="">Select a region</option>
                    @foreach (config('group.regions') as $regionOption)
                        <option value="{{ $regionOption }}">{{ $regionOption }}</option>
                    @endforeach
                </select>
            @else
                <input
                    id="{{ $id }}"
                    type="{{ $field['type'] ?? 'text' }}"
                    wire:model="{{ $name }}"
                    autocomplete="{{ $field['autocomplete'] }}"
                    @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                    class="{{ $inputClass }}"
                    @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                >
            @endif
            @error($name) <p id="{{ $id }}-error" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    @endforeach

    <div class="sm:col-span-2">
        <label for="{{ $prefix }}-street" class="block text-sm font-medium text-fg mb-1.5">Street or landmark <span class="font-normal text-muted">(optional)</span></label>
        <input id="{{ $prefix }}-street" type="text" wire:model="street" autocomplete="street-address" placeholder="House number, street, nearby landmark" class="{{ $inputClass }}">
        @error('street') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
