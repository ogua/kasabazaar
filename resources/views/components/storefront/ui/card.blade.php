@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => "bg-surface border border-border rounded-lg $padding"]) }}>
    {{ $slot }}
</div>
