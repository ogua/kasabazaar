<x-filament-panels::page class="fi-dashboard-page">
    {{-- Zone 1: the investor's own money --}}
    <section class="overflow-hidden rounded-2xl border-2 border-emerald-500 bg-emerald-50/60 shadow-sm dark:border-emerald-500/70 dark:bg-emerald-950/30">
        <header class="flex flex-wrap items-center justify-between gap-3 bg-emerald-600 px-5 py-3 text-white">
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-wallet" class="h-6 w-6" />
                <div>
                    <h2 class="text-base font-bold tracking-wide uppercase">My Investment</h2>
                    <p class="text-xs text-emerald-100">Your personal account &mdash; only these figures are your money.</p>
                </div>
            </div>
            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">
                {{ auth()->user()->investor?->name ?? auth()->user()->name }}
            </span>
        </header>

        <div class="p-4 sm:p-5">
            <x-filament-widgets::widgets :columns="1" :widgets="$this->getInvestorWidgets()" />
        </div>
    </section>

    {{-- Zone 2: the company as a whole --}}
    <section class="overflow-hidden rounded-2xl border-2 border-sky-600 bg-sky-50/60 shadow-sm dark:border-sky-500/70 dark:bg-sky-950/30">
        <header class="flex flex-wrap items-center justify-between gap-3 bg-sky-700 px-5 py-3 text-white">
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-building-office-2" class="h-6 w-6" />
                <div>
                    <h2 class="text-base font-bold tracking-wide uppercase">Company Performance &mdash; {{ config('group.parent.name') }}</h2>
                    <p class="text-xs text-sky-100">Figures for the whole business. These are <strong>not</strong> your balance or your returns.</p>
                </div>
            </div>
            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">For information only</span>
        </header>

        <div class="p-4 sm:p-5">
            <x-filament-widgets::widgets :columns="$this->getColumns()" :widgets="$this->getCompanyWidgets()" />
        </div>
    </section>
</x-filament-panels::page>
