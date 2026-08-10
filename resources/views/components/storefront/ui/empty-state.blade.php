@props(['icon' => 'box', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-16 px-6']) }}>
    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-surface-muted text-muted mb-4">
        <x-storefront.icon :name="$icon" class="w-6 h-6" />
    </div>
    <h3 class="font-display font-semibold text-lg text-fg mb-1">{{ $title }}</h3>
    @if ($description)
        <p class="text-muted text-sm max-w-sm mb-6">{{ $description }}</p>
    @endif
    @isset($action)
        {{ $action }}
    @endisset
</div>
