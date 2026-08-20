@props([
    'title',
    'intro' => null,
    'sections' => [],
])

{{--
    Shared chrome for the five legal pages (privacy, terms, delivery, returns,
    cookies). $sections is a flat array of heading strings used to build the
    on-page contents list; the prose itself lives in $slot, where each
    <section> must carry an id matching Str::slug() of its heading.
--}}

<div class="bg-navy-900 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        <h1 class="font-display font-bold text-3xl md:text-4xl mb-3">{{ $title }}</h1>
        @if ($intro)
            <p class="text-white/70 leading-relaxed max-w-2xl">{{ $intro }}</p>
        @endif
        <p class="text-white/50 text-xs uppercase tracking-wide mt-5">
            Effective {{ config('group.legal.effective_date') }} &middot; {{ config('group.company.legal_name') }}
        </p>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <x-storefront.ui.breadcrumb :items="[['label' => $title]]" />

    <div class="grid lg:grid-cols-4 gap-10">
        @if (filled($sections))
            <nav aria-label="On this page" class="lg:col-span-1 order-2 lg:order-1">
                <div class="lg:sticky lg:top-20">
                    <h2 class="font-display font-semibold text-xs uppercase tracking-wide text-navy-900 mb-3">On this page</h2>
                    <ol class="space-y-2 text-sm text-muted">
                        @foreach ($sections as $index => $section)
                            <li>
                                <a href="#{{ \Illuminate\Support\Str::slug($section) }}" class="hover:text-accent">
                                    <span class="tabular-nums text-xs text-muted/70 mr-1">{{ $index + 1 }}.</span>{{ $section }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </nav>
        @endif

        <div class="{{ filled($sections) ? 'lg:col-span-3 order-1 lg:order-2' : 'lg:col-span-4' }} legal-prose max-w-[70ch]">
            {{ $slot }}
        </div>
    </div>

    <div class="mt-14 rounded-lg border border-border bg-surface p-6 sm:p-8">
        <h2 class="font-display font-semibold text-lg text-navy-900 mb-2">Questions about this policy?</h2>
        <p class="text-sm text-muted leading-relaxed mb-4 max-w-2xl">
            Write to us and we will respond within five business days. This policy applies to
            {{ config('app.name') }} only — the other {{ config('group.parent.name') }} businesses publish their own
            policies on their own sites.
        </p>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <a href="mailto:{{ config('group.contact.legal_email') }}" class="inline-flex items-center gap-2 font-medium text-navy-900 hover:text-accent">
                <x-storefront.icon name="mail" class="w-4 h-4" />
                {{ config('group.contact.legal_email') }}
            </a>
            <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="inline-flex items-center gap-2 font-medium text-navy-900 hover:text-accent">
                <x-storefront.icon name="phone" class="w-4 h-4" />
                {{ config('group.contact.phone_gh') }}
            </a>
            <a href="{{ route('storefront.contact') }}" class="inline-flex items-center gap-1.5 font-medium text-navy-500 hover:text-accent">
                Contact form
                <x-storefront.icon name="arrow-right" class="w-3.5 h-3.5" />
            </a>
        </div>
    </div>

    <nav aria-label="Other policies" class="mt-10 border-t border-border pt-6">
        <h2 class="font-display font-semibold text-xs uppercase tracking-wide text-navy-900 mb-3">Our other policies</h2>
        <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
            @foreach ([
                'storefront.privacy' => 'Privacy Policy',
                'storefront.terms' => 'Terms of Use',
                'storefront.delivery-policy' => 'Delivery Policy',
                'storefront.returns' => 'Returns & Refunds',
                'storefront.cookies' => 'Cookie Policy',
            ] as $policyRoute => $label)
                @unless (request()->routeIs($policyRoute))
                    <li><a href="{{ route($policyRoute) }}" class="hover:text-accent">{{ $label }}</a></li>
                @endunless
            @endforeach
        </ul>
    </nav>
</div>
