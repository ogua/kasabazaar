@props(['meta'])

@php
    $current = (int) ($meta['current_page'] ?? 1);
    $last = (int) ($meta['last_page'] ?? 1);

    if ($last <= 1) {
        return;
    }

    $window = 2;
    $pages = collect(range(max(1, $current - $window), min($last, $current + $window)));
@endphp

<nav aria-label="Pagination" {{ $attributes->merge(['class' => 'flex items-center justify-center gap-1 mt-8']) }}>
    <button
        type="button"
        wire:click="previousPage"
        @disabled($current <= 1)
        class="inline-flex items-center justify-center w-9 h-9 rounded-sm border border-border text-navy-900 hover:bg-surface-muted disabled:opacity-40 disabled:pointer-events-none"
        aria-label="Previous page"
    >
        <x-storefront.icon name="chevron-left" class="w-4 h-4" />
    </button>

    @if (! $pages->contains(1))
        <button type="button" wire:click="gotoPage(1)" class="inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-sm text-sm font-medium text-navy-900 hover:bg-surface-muted">1</button>
        <span class="px-1 text-muted">&hellip;</span>
    @endif

    @foreach ($pages as $page)
        <button
            type="button"
            wire:click="gotoPage({{ $page }})"
            @if ($page === $current) aria-current="page" @endif
            class="inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-sm text-sm font-medium {{ $page === $current ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-surface-muted' }}"
        >
            {{ $page }}
        </button>
    @endforeach

    @if (! $pages->contains($last))
        <span class="px-1 text-muted">&hellip;</span>
        <button type="button" wire:click="gotoPage({{ $last }})" class="inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-sm text-sm font-medium text-navy-900 hover:bg-surface-muted">{{ $last }}</button>
    @endif

    <button
        type="button"
        wire:click="nextPage"
        @disabled($current >= $last)
        class="inline-flex items-center justify-center w-9 h-9 rounded-sm border border-border text-navy-900 hover:bg-surface-muted disabled:opacity-40 disabled:pointer-events-none"
        aria-label="Next page"
    >
        <x-storefront.icon name="chevron-right" class="w-4 h-4" />
    </button>
</nav>
