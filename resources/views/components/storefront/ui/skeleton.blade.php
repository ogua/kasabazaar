@props(['class' => 'h-4 w-full'])

<div {{ $attributes->merge(['class' => "$class rounded-sm bg-surface-muted animate-pulse"]) }}></div>
