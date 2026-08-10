@props(['items' => []])

{{--
    $items: array of ['label' => string, 'href' => string|null]. The last item
    (or any item with href = null) renders as the current page, not a link.
--}}

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-sm mb-6']) }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-muted">
        <li class="flex items-center gap-1.5">
            <a href="{{ route('storefront.home') }}" class="hover:text-navy-900">Home</a>
            <x-storefront.icon name="chevron-right" class="w-3.5 h-3.5" />
        </li>
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1.5">
                @if (! empty($item['href']) && ! $loop->last)
                    <a href="{{ $item['href'] }}" class="hover:text-navy-900">{{ $item['label'] }}</a>
                    <x-storefront.icon name="chevron-right" class="w-3.5 h-3.5" />
                @else
                    <span class="text-fg font-medium" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
