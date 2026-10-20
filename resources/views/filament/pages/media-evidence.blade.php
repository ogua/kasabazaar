@php
    $media = $this->getMedia();
    $typeCounts = $this->getTypeCounts();
    $items = $media->getCollection()->map(fn ($item) => $this->describe($item))->values();
    $focusedShipment = $this->getFilteredShipment();
    $typeTabs = [
        '' => ['label' => 'All media', 'icon' => 'heroicon-m-squares-2x2'],
        'image' => ['label' => 'Photos', 'icon' => 'heroicon-m-photo'],
        'video' => ['label' => 'Videos', 'icon' => 'heroicon-m-film'],
        'document' => ['label' => 'Documents', 'icon' => 'heroicon-m-document-text'],
    ];
@endphp

<x-filament-panels::page>
    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($typeTabs as $type => $tab)
            <button type="button" wire:click="setType('{{ $type }}')"
                @class([
                    'flex items-center gap-4 rounded-xl bg-white p-4 text-start shadow-sm ring-1 transition hover:shadow-md dark:bg-gray-900',
                    'ring-2 ring-primary-600 dark:ring-primary-500' => $this->type === $type,
                    'ring-gray-950/5 dark:ring-white/10' => $this->type !== $type,
                ])>
                <span @class([
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg',
                    'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $this->type === $type,
                    'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' => $this->type !== $type,
                ])>
                    <x-filament::icon :icon="$tab['icon']" class="h-6 w-6" />
                </span>
                <span class="min-w-0">
                    <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $tab['label'] }}</span>
                    <span class="block text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($typeCounts[$type] ?? 0) }}</span>
                </span>
            </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <x-filament::section compact>
        <div class="flex flex-col gap-4">
            {{ $this->form }}

            @if ($focusedShipment || $this->hasActiveFilters())
                <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-white/5">
                    @if ($focusedShipment)
                        <x-filament::badge color="info" icon="heroicon-m-truck">
                            Shipment {{ $focusedShipment->tracking_number ?? $focusedShipment->shipping_reference }}
                            @if ($focusedShipment->client) · {{ $focusedShipment->client->name }} @endif
                        </x-filament::badge>
                        <x-filament::link tag="button" size="sm" color="gray" wire:click="$set('shipment', '')" icon="heroicon-m-x-mark">
                            Show all shipments
                        </x-filament::link>
                    @endif

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format($media->total()) }} {{ \Illuminate\Support\Str::plural('file', $media->total()) }} match
                    </span>

                    <x-filament::link tag="button" size="sm" color="danger" wire:click="clearFilters" icon="heroicon-m-arrow-path" class="ms-auto">
                        Reset filters
                    </x-filament::link>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- Bulk selection toolbar --}}
    @if (count($this->selected) > 0)
        <div class="sticky top-16 z-10 flex flex-wrap items-center gap-3 rounded-xl bg-primary-50 px-4 py-3 ring-1 ring-primary-600/20 dark:bg-primary-500/10 dark:ring-primary-400/30">
            <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ count($this->selected) }} selected</span>
            <x-filament::link tag="button" size="sm" wire:click="selectPage">Select all on page</x-filament::link>
            <x-filament::link tag="button" size="sm" color="gray" wire:click="$set('selected', [])">Clear</x-filament::link>
            <div class="ms-auto">{{ $this->deleteSelectedAction }}</div>
        </div>
    @endif

    {{-- Gallery --}}
    @if ($items->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 rounded-xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5">
                <x-filament::icon icon="heroicon-o-photo" class="h-7 w-7 text-gray-400" />
            </span>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">No media found</h3>
            <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
                @if ($this->hasActiveFilters())
                    Nothing matches the current filters. Try widening the date range or resetting the filters.
                @else
                    No photos, videos or documents have been uploaded for this branch's shipments yet.
                @endif
            </p>
            <div class="flex gap-2">
                @if ($this->hasActiveFilters())
                    <x-filament::button color="gray" wire:click="clearFilters">Reset filters</x-filament::button>
                @endif
                <x-filament::button icon="heroicon-m-arrow-up-tray" wire:click="mountAction('upload')">Upload media</x-filament::button>
            </div>
        </div>
    @else
        <div
            wire:key="gallery-{{ md5($items->pluck('id')->join(',')) }}"
            x-data="{
                items: @js($items),
                open: false,
                index: 0,
                get current() { return this.items[this.index] ?? null },
                show(i) { this.index = i; this.open = true },
                close() { this.open = false; this.$refs.player?.pause?.() },
                next() { this.$refs.player?.pause?.(); this.index = (this.index + 1) % this.items.length },
                prev() { this.$refs.player?.pause?.(); this.index = (this.index - 1 + this.items.length) % this.items.length },
                remove() { const id = this.current.id; this.close(); this.$wire.mountAction('deleteMedia', { id }) },
                focusOn(filter, value) { this.close(); this.$wire.focusOn(filter, value) },
                stageClass(stage) {
                    return {
                        'pickup': 'bg-amber-500/90',
                        'in-transit': 'bg-sky-600/90',
                        'delivery': 'bg-emerald-600/90',
                        'post-delivery': 'bg-purple-600/90',
                    }[stage] ?? 'bg-gray-600/90'
                },
            }"
            x-on:keydown.window="if (! open) return; if ($event.key === 'Escape') close(); if ($event.key === 'ArrowRight') next(); if ($event.key === 'ArrowLeft') prev();"
        >
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6">
                @foreach ($items as $i => $item)
                    <div wire:key="media-{{ $item['id'] }}"
                        @class([
                            'group relative aspect-[4/3] overflow-hidden rounded-xl bg-gray-100 shadow-sm ring-1 transition hover:shadow-lg dark:bg-gray-800',
                            'ring-2 ring-primary-600 dark:ring-primary-500' => in_array($item['id'], $this->selected, true),
                            'ring-gray-950/5 dark:ring-white/10' => ! in_array($item['id'], $this->selected, true),
                        ])>
                        <button type="button" x-on:click="show({{ $i }})" class="absolute inset-0 h-full w-full focus:outline-none" aria-label="View {{ $item['fileName'] }}">
                            @if ($item['type'] === 'image')
                                <img src="{{ $item['url'] }}" alt="{{ $item['caption'] ?? $item['stageLabel'] }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                            @elseif ($item['type'] === 'video')
                                <video src="{{ $item['url'] }}#t=0.5" preload="metadata" muted playsinline class="h-full w-full bg-black object-cover"></video>
                                <span class="absolute inset-0 flex items-center justify-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-sm transition group-hover:scale-110">
                                        <x-filament::icon icon="heroicon-s-play" class="ms-0.5 h-6 w-6" />
                                    </span>
                                </span>
                            @else
                                <span class="flex h-full w-full flex-col items-center gap-1.5 bg-gradient-to-br from-gray-50 to-gray-200 px-3 pt-6 sm:gap-2 sm:pt-9 dark:from-gray-800 dark:to-gray-900">
                                    <span class="relative">
                                        <x-filament::icon icon="heroicon-o-document-text" class="h-9 w-9 text-gray-400 sm:h-12 sm:w-12 dark:text-gray-500" />
                                        <span class="absolute -bottom-1 -end-3 rounded bg-primary-600 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-white shadow">{{ $item['extension'] ?: 'FILE' }}</span>
                                    </span>
                                    <span class="w-full truncate text-center text-xs font-medium text-gray-600 dark:text-gray-300" title="{{ $item['fileName'] }}">{{ $item['fileName'] }}</span>
                                </span>
                            @endif

                            {{-- Overlay --}}
                            <span class="pointer-events-none absolute inset-x-0 bottom-0 flex flex-col gap-0.5 bg-gradient-to-t from-black/90 via-black/60 to-transparent px-3 pb-2.5 pt-8 text-start sm:pb-3 text-white">
                                <span class="truncate text-sm font-semibold">{{ $item['clientName'] }}</span>
                                <span class="truncate text-[11px] text-white/75">
                                    {{ $item['trackingNumber'] ?? $item['reference'] }}@if ($item['container']) · {{ $item['container'] }}@endif
                                </span>
                                <span class="grid grid-rows-[0fr] transition-all duration-300 group-hover:grid-rows-[1fr]">
                                    <span class="overflow-hidden">
                                        <span class="mt-1 flex flex-col gap-0.5 border-t border-white/20 pt-1.5 text-[11px] text-white/85">
                                            <span class="flex items-center gap-1"><x-filament::icon icon="heroicon-m-calendar" class="h-3.5 w-3.5" /> {{ $item['date'] }}</span>
                                            <span class="flex items-center gap-1"><x-filament::icon icon="heroicon-m-clock" class="h-3.5 w-3.5" /> {{ $item['time'] }}</span>
                                            <span class="flex items-center gap-1 truncate"><x-filament::icon icon="heroicon-m-user" class="h-3.5 w-3.5" /> {{ $item['uploader'] }}</span>
                                            @if ($item['caption'])
                                                <span class="line-clamp-2 italic text-white/70">“{{ $item['caption'] }}”</span>
                                            @endif
                                        </span>
                                    </span>
                                </span>
                            </span>
                        </button>

                        {{-- Stage + type --}}
                        <span class="pointer-events-none absolute start-2 top-2 flex items-center gap-1">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white shadow backdrop-blur-sm"
                                x-bind:class="stageClass(@js($item['stage']))">{{ $item['stageLabel'] }}</span>
                        </span>

                        @if ($item['removable'])
                            <label class="absolute end-2 top-2 flex h-7 w-7 cursor-pointer items-center justify-center rounded-md bg-white/90 shadow transition dark:bg-gray-900/90 {{ in_array($item['id'], $this->selected, true) ? '' : 'opacity-0 group-hover:opacity-100 focus-within:opacity-100' }}"
                                title="Select for removal">
                                <x-filament::input.checkbox wire:model.live="selected" value="{{ $item['id'] }}" />
                            </label>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Lightbox --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-40 flex bg-gray-950/95 backdrop-blur-sm"
                    role="dialog" aria-modal="true" x-trap.noscroll="open">
                    <template x-if="current">
                        <div class="flex h-full w-full flex-col lg:flex-row">
                            {{-- Viewer --}}
                            <div class="relative flex min-h-0 flex-1 flex-col">
                                <div class="flex items-center gap-3 px-4 py-3 text-white">
                                    <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide" x-bind:class="stageClass(current.stage)" x-text="current.stageLabel"></span>
                                    <span class="truncate text-sm text-white/80" x-text="current.fileName"></span>
                                    <span class="ms-auto text-sm text-white/60" x-text="(index + 1) + ' / ' + items.length"></span>
                                    <button type="button" x-on:click="close()" class="rounded-lg p-1.5 text-white/70 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close">
                                        <x-filament::icon icon="heroicon-m-x-mark" class="h-6 w-6" />
                                    </button>
                                </div>

                                <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-16">
                                    <template x-if="current.type === 'image'">
                                        <img x-bind:src="current.url" x-bind:alt="current.caption || current.stageLabel" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl" />
                                    </template>
                                    <template x-if="current.type === 'video'">
                                        <video x-ref="player" x-bind:src="current.url" controls autoplay playsinline class="max-h-full max-w-full rounded-lg bg-black shadow-2xl"></video>
                                    </template>
                                    <template x-if="current.type === 'document' && current.extension === 'PDF'">
                                        <iframe x-bind:src="current.url" class="h-full w-full max-w-4xl rounded-lg bg-white shadow-2xl" title="Document preview"></iframe>
                                    </template>
                                    <template x-if="current.type === 'document' && current.extension !== 'PDF'">
                                        <div class="flex flex-col items-center gap-4 rounded-2xl bg-white/5 p-10 text-center text-white ring-1 ring-white/10">
                                            <x-filament::icon icon="heroicon-o-document-text" class="h-20 w-20 text-white/60" />
                                            <span class="rounded bg-primary-600 px-2 py-0.5 text-xs font-bold uppercase tracking-wider" x-text="current.extension || 'FILE'"></span>
                                            <p class="max-w-sm break-all text-sm text-white/80" x-text="current.fileName"></p>
                                            <p class="text-xs text-white/50">This file type can't be previewed in the browser.</p>
                                            <a x-bind:href="current.url" download class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-100">
                                                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" /> Download
                                            </a>
                                        </div>
                                    </template>

                                    <template x-if="items.length > 1">
                                        <div>
                                            <button type="button" x-on:click="prev()" class="absolute start-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2.5 text-white hover:bg-white/20 sm:start-4" aria-label="Previous">
                                                <x-filament::icon icon="heroicon-m-chevron-left" class="h-6 w-6" />
                                            </button>
                                            <button type="button" x-on:click="next()" class="absolute end-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2.5 text-white hover:bg-white/20 sm:end-4" aria-label="Next">
                                                <x-filament::icon icon="heroicon-m-chevron-right" class="h-6 w-6" />
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Details panel --}}
                            <aside class="flex max-h-[45vh] w-full shrink-0 flex-col overflow-y-auto bg-white text-sm dark:bg-gray-900 lg:max-h-none lg:w-96">
                                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-white/10">
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Evidence details</p>
                                        <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white" x-text="current.caption || 'No caption'"></p>
                                    </div>
                                    <button type="button" x-on:click="close()" class="hidden rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 lg:block" aria-label="Close">
                                        <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                                    </button>
                                </div>

                                <div class="flex flex-col gap-5 px-5 py-4">
                                    {{-- Client --}}
                                    <section>
                                        <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                                            <x-filament::icon icon="heroicon-m-user-circle" class="h-4 w-4" /> Client
                                        </h4>
                                        <dl class="grid grid-cols-3 gap-x-3 gap-y-1.5">
                                            <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                                            <dd class="col-span-2 font-medium text-gray-950 dark:text-white" x-text="current.clientName"></dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                                            <dd class="col-span-2">
                                                <a x-show="current.clientPhone" x-bind:href="'tel:' + current.clientPhone" class="text-primary-600 hover:underline dark:text-primary-400" x-text="current.clientPhone"></a>
                                                <span x-show="! current.clientPhone" class="text-gray-400">—</span>
                                            </dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                                            <dd class="col-span-2 min-w-0 truncate">
                                                <a x-show="current.clientEmail" x-bind:href="'mailto:' + current.clientEmail" x-bind:title="current.clientEmail" class="text-primary-600 hover:underline dark:text-primary-400" x-text="current.clientEmail"></a>
                                                <span x-show="! current.clientEmail" class="text-gray-400">—</span>
                                            </dd>
                                            <template x-if="current.receivers.length">
                                                <dt class="text-gray-500 dark:text-gray-400">Receiver</dt>
                                            </template>
                                            <template x-if="current.receivers.length">
                                                <dd class="col-span-2 text-gray-950 dark:text-white" x-text="current.receivers.join(', ')"></dd>
                                            </template>
                                        </dl>
                                    </section>

                                    {{-- Shipment --}}
                                    <section>
                                        <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                                            <x-filament::icon icon="heroicon-m-truck" class="h-4 w-4" /> Shipment
                                        </h4>
                                        <dl class="grid grid-cols-3 gap-x-3 gap-y-1.5">
                                            <dt class="text-gray-500 dark:text-gray-400">Tracking</dt>
                                            <dd class="col-span-2 font-mono text-gray-950 dark:text-white" x-text="current.trackingNumber || '—'"></dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Reference</dt>
                                            <dd class="col-span-2 font-mono text-gray-950 dark:text-white" x-text="current.reference || '—'"></dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Container</dt>
                                            <dd class="col-span-2 text-gray-950 dark:text-white" x-text="current.container || 'Not assigned'"></dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                                            <dd class="col-span-2 text-gray-950 dark:text-white" x-text="current.status || '—'"></dd>
                                        </dl>
                                    </section>

                                    {{-- Evidence --}}
                                    <section>
                                        <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                                            <x-filament::icon icon="heroicon-m-camera" class="h-4 w-4" /> Evidence
                                        </h4>
                                        <dl class="grid grid-cols-3 gap-x-3 gap-y-1.5">
                                            <dt class="text-gray-500 dark:text-gray-400">Stage</dt>
                                            <dd class="col-span-2 text-gray-950 dark:text-white" x-text="current.stageLabel"></dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Date</dt>
                                            <dd class="col-span-2 text-gray-950 dark:text-white" x-text="current.date"></dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Time</dt>
                                            <dd class="col-span-2 text-gray-950 dark:text-white">
                                                <span x-text="current.time"></span>
                                                <span class="text-gray-400" x-text="'(' + current.ago + ')'"></span>
                                            </dd>
                                            <dt class="text-gray-500 dark:text-gray-400">Uploaded by</dt>
                                            <dd class="col-span-2 text-gray-950 dark:text-white" x-text="current.uploader"></dd>
                                            <dt class="text-gray-500 dark:text-gray-400">File</dt>
                                            <dd class="col-span-2 break-all text-gray-950 dark:text-white">
                                                <span x-text="current.fileName"></span>
                                                <span class="text-gray-400" x-show="current.size" x-text="'· ' + current.size"></span>
                                                <span class="text-danger-600" x-show="! current.size">· missing from storage</span>
                                            </dd>
                                        </dl>
                                    </section>

                                    {{-- Quick filters --}}
                                    <section class="flex flex-wrap gap-2">
                                        <button type="button" x-show="current.clientId" x-on:click="focusOn('client', current.clientId)"
                                            class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                                            All media for this client
                                        </button>
                                        <button type="button" x-show="current.containerNumber" x-on:click="focusOn('container', current.containerNumber)"
                                            class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                                            All media in <span x-text="current.container"></span>
                                        </button>
                                        <button type="button" x-show="current.shipmentId" x-on:click="focusOn('shipment', current.shipmentId)"
                                            class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                                            This shipment only
                                        </button>
                                    </section>
                                </div>

                                <div class="mt-auto flex flex-wrap gap-2 border-t border-gray-100 px-5 py-4 dark:border-white/10">
                                    <a x-bind:href="current.url" download
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                                        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" /> Download
                                    </a>
                                    <a x-bind:href="current.url" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/10">
                                        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" /> Open
                                    </a>
                                    <a x-show="current.shipmentUrl" x-bind:href="current.shipmentUrl"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/10">
                                        <x-filament::icon icon="heroicon-m-truck" class="h-4 w-4" /> Shipment
                                    </a>
                                    <button type="button" x-show="current.removable" x-on:click="remove()"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-danger-600 hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-500/10">
                                        <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" /> Remove
                                    </button>
                                </div>
                            </aside>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <x-filament::pagination :paginator="$media" />
    @endif
</x-filament-panels::page>
