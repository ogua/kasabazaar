@props(['variant' => 'info', 'dismissible' => false])

@php
    $variants = [
        'error' => ['bg' => 'bg-error/5 border-error/20 text-error', 'icon' => 'exclamation-circle'],
        'success' => ['bg' => 'bg-success/5 border-success/20 text-success', 'icon' => 'check-circle'],
        'warning' => ['bg' => 'bg-warning/5 border-warning/20 text-warning', 'icon' => 'exclamation-circle'],
        'info' => ['bg' => 'bg-navy-900/5 border-navy-900/15 text-navy-900', 'icon' => 'exclamation-circle'],
    ];
    $v = $variants[$variant] ?? $variants['info'];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {$v['bg']}"]) }} role="alert">
    <x-storefront.icon :name="$v['icon']" class="w-5 h-5 shrink-0 mt-0.5" />
    <div class="flex-1 leading-relaxed">{{ $slot }}</div>
</div>
