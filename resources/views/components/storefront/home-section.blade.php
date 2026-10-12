{{-- Homepage product row: a white panel with a title bar and a "See all" link.
     The `deal` tone swaps the title bar to the brand red for time-sensitive rows. --}}
@props([
    'title',
    'href' => null,
    'icon' => null,
    'tone' => 'default',
])

<section {{ $attributes->merge(['class' => 'bg-surface border border-border rounded-lg overflow-hidden']) }}>
    <div @class([
        'flex items-center justify-between gap-4 px-4 sm:px-5 h-12',
        'bg-accent text-white' => $tone === 'deal',
        'border-b border-border' => $tone !== 'deal',
    ])>
        <h2 @class([
            'flex items-center gap-2 font-display font-semibold text-lg',
            'text-navy-900' => $tone !== 'deal',
        ])>
            @if ($icon)
                <x-storefront.icon :name="$icon" class="w-5 h-5" />
            @endif
            {{ $title }}
        </h2>
        @if ($href)
            <a href="{{ $href }}" @class([
                'inline-flex items-center gap-1 text-sm font-semibold',
                'text-white/90 hover:text-white' => $tone === 'deal',
                'text-navy-500 hover:text-accent' => $tone !== 'deal',
            ])>
                See all
                <x-storefront.icon name="chevron-right" class="w-4 h-4" />
            </a>
        @endif
    </div>
    <div class="p-3 sm:p-4">
        {{ $slot }}
    </div>
</section>
