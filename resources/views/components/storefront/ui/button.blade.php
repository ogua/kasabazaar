@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-navy-900 text-white hover:bg-navy-700 focus-visible:outline-accent disabled:bg-navy-900/50',
        'accent' => 'bg-accent text-white hover:bg-accent-hover focus-visible:outline-accent disabled:bg-accent/50',
        'secondary' => 'bg-white text-navy-900 border border-navy-900 hover:bg-navy-900 hover:text-white focus-visible:outline-accent disabled:opacity-50',
        'tertiary' => 'bg-transparent text-navy-500 hover:text-navy-900 underline-offset-4 hover:underline focus-visible:outline-accent disabled:opacity-50',
        'danger' => 'bg-white text-error border border-error hover:bg-error hover:text-white focus-visible:outline-error disabled:opacity-50',
    ];

    $sizes = [
        'sm' => 'text-sm px-3 py-1.5 gap-1.5',
        'md' => 'text-sm px-4 py-2.5 gap-2',
        'lg' => 'text-base px-6 py-3 gap-2',
    ];

    $base = 'inline-flex items-center justify-center rounded-sm font-semibold transition-colors duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed';
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
