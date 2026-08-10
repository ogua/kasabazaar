<div class="fixed top-24 right-4 z-1200 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
    @foreach ($messages as $toast)
        <div
            wire:key="toast-{{ $toast['id'] }}"
            x-data="{ show: false }"
            x-init="requestAnimationFrame(() => show = true); setTimeout(() => { show = false; setTimeout(() => $wire.dismiss('{{ $toast['id'] }}'), 200) }, 4000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-out duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="pointer-events-auto flex items-start gap-3 rounded-lg shadow-float px-4 py-3 text-sm text-white {{ $toast['type'] === 'error' ? 'bg-error' : 'bg-success' }}"
        >
            <x-storefront.icon :name="$toast['type'] === 'error' ? 'exclamation-circle' : 'check-circle'" class="w-5 h-5 shrink-0 mt-0.5" />
            <p class="flex-1 leading-relaxed">{{ $toast['message'] }}</p>
            <button @click="show = false; setTimeout(() => $wire.dismiss('{{ $toast['id'] }}'), 200)" class="shrink-0 opacity-75 hover:opacity-100" aria-label="Dismiss">
                <x-storefront.icon name="x-mark" class="w-4 h-4" />
            </button>
        </div>
    @endforeach
</div>
