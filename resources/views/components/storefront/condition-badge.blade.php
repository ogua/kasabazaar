@props(['condition' => null])

@php
    $c = \App\Support\ProductCondition::for($condition);
    $tones = [
        'success' => 'bg-success/10 text-success',
        'info' => 'bg-navy-50 text-navy-700',
        'warning' => 'bg-warning/10 text-warning',
        'neutral' => 'bg-surface-muted text-fg',
    ];
    $popoverId = 'condition-'.\Illuminate\Support\Str::random(8);
@endphp

{{--
    Condition chip with a popover that explains it. Opens on hover, keyboard focus
    and tap; closes on Escape or outside tap. The panel is teleported to <body> and
    positioned from the chip's rectangle so product cards (overflow-hidden) do not clip it.
--}}
<span
    x-data="{
        open: false,
        top: 0,
        left: 0,
        place() {
            const r = this.$refs.chip.getBoundingClientRect();
            const w = 288;
            this.left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
            this.top = r.bottom + 8;
        },
        show() { this.place(); this.open = true; },
    }"
    x-on:mouseenter="show()"
    x-on:mouseleave="open = false"
    x-on:keydown.escape.window="open = false"
    x-on:scroll.window="open = false"
    {{ $attributes->class('inline-flex') }}
>
    <button
        type="button"
        x-ref="chip"
        x-on:focus="show()"
        x-on:blur="open = false"
        x-on:click.prevent="open ? (open = false) : show()"
        aria-describedby="{{ $popoverId }}"
        class="inline-flex items-center gap-1 rounded-sm px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide cursor-help focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent {{ $tones[$c['tone']] }}"
    >
        {{ $c['label'] }}
        <x-storefront.icon name="information-circle" class="w-3.5 h-3.5" />
    </button>

    <template x-teleport="body">
        <div
            id="{{ $popoverId }}"
            role="tooltip"
            x-show="open"
            x-cloak
            x-transition.opacity.duration.100ms
            :style="`top:${top}px;left:${left}px`"
            class="fixed z-50 w-72 rounded-lg border border-border bg-surface p-3 shadow-float text-left normal-case tracking-normal"
        >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted mb-2">Item conditions</p>
            <ul class="space-y-2">
                @foreach (\App\Support\ProductCondition::all() as $key => $option)
                    <li @class([
                        'rounded-sm px-2 py-1.5',
                        'bg-accent-soft/50 ring-1 ring-accent/40' => $key === $c['key'],
                    ])>
                        <p class="text-sm font-semibold text-navy-900">
                            {{ $option['label'] }}
                            @if ($key === $c['key'])
                                <span class="ml-1 text-[10px] font-bold uppercase tracking-wide text-accent-hover">This item</span>
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs leading-relaxed text-muted">{{ $option['description'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </template>
</span>
