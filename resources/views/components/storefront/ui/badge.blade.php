@props(['variant' => 'default'])

@php
    $variants = [
        'default' => 'bg-surface-muted text-fg',
        'accent' => 'bg-accent-soft text-accent-hover',
        'navy' => 'bg-navy-900 text-white',
        'success' => 'bg-success/10 text-success',
        'warning' => 'bg-warning/10 text-warning',
        'error' => 'bg-error/10 text-error',
        'muted' => 'bg-transparent text-muted border border-border',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-sm px-2 py-0.5 text-xs font-semibold uppercase tracking-wide '.($variants[$variant] ?? $variants['default'])]) }}>
    {{ $slot }}
</span>
