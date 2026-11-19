{{--
   Headline figures plus the client-growth and monthly-revenue charts, from
   App\Services\PublicInvestorMetrics::summary(). Shared by the investors page and the homepage.

   @include('site.partials.growth-charts', ['metrics' => $metrics, 'showRevenue' => true])
   Needs css/investors.css and js/charts.js on the page.
--}}
@php
   $kpis = $metrics['kpis'];
   $months = $metrics['months'];
   $peakRevenue = max(array_column($months, 'revenue') ?: [0]);

   // Month ticks carry the year on the first month and every January.
   $months = array_map(fn (array $m, int $i): array => [
       ...$m,
       'short' => $i === 0 || $m['short'] === 'Jan' ? $m['short'].' '.substr($m['key'], 2, 2) : $m['short'],
   ], $months, array_keys($months));

   $clientPoints = array_map(fn (array $m): array => [
       'label' => $m['label'],
       'short' => $m['short'],
       'value' => $m['clients_total'],
       'display' => number_format($m['clients_total']).' clients',
   ], $months);

   // With the figures switched off the bars keep their shape, but every value is a share of the
   // best month instead of dollars.
   $revenuePoints = array_map(fn (array $m): array => [
       'label' => $m['label'],
       'short' => $m['short'],
       'value' => $showRevenue ? $m['revenue'] : ($peakRevenue > 0 ? round($m['revenue'] / $peakRevenue * 100, 1) : 0),
       'display' => $showRevenue
           ? '$'.number_format($m['revenue'])
           : ($peakRevenue > 0 ? round($m['revenue'] / $peakRevenue * 100).'% of best month' : '—'),
   ], $months);

   $growthBadge = function (?float $growth): string {
       if ($growth === null) {
           return '';
       }
       $up = $growth >= 0;

       return '<span class="ksr-kpi__trend '.($up ? 'is-up' : 'is-down').'"><i class="fa-solid '.($up ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down').'" aria-hidden="true"></i> '
           .($up ? '+' : '').number_format($growth, 1).'% <span class="visually-hidden">versus the previous 12 months</span></span>';
   };
   $firstMonth = $months[0]['label'] ?? '';
   $lastMonth = end($months)['label'] ?? '';
@endphp

<div class="ksr-growth">
         <!-- Headline figures -->
         <div class="row g-3 g-lg-4 mb-5">
            <div class="col-6 col-lg-3">
               <div class="ksr-kpi">
                  <span class="ksr-kpi__icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                  <p class="ksr-kpi__label">Clients served</p>
                  <p class="ksr-kpi__value">{{ number_format($kpis['clients_total']) }}</p>
                  <p class="ksr-kpi__note">All time</p>
               </div>
            </div>
            <div class="col-6 col-lg-3">
               <div class="ksr-kpi">
                  <span class="ksr-kpi__icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
                  <p class="ksr-kpi__label">New clients</p>
                  <p class="ksr-kpi__value">{{ number_format($kpis['clients_last_12']) }}</p>
                  <p class="ksr-kpi__note">Last 12 months {!! $growthBadge($kpis['clients_growth']) !!}</p>
               </div>
            </div>
            <div class="col-6 col-lg-3">
               <div class="ksr-kpi ksr-kpi--accent">
                  <span class="ksr-kpi__icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
                  <p class="ksr-kpi__label">Revenue</p>
                  @if ($showRevenue)
                     <p class="ksr-kpi__value">{{ \App\Services\PublicInvestorMetrics::compactMoney($kpis['revenue_last_12']) }}</p>
                     <p class="ksr-kpi__note">Last 12 months {!! $growthBadge($kpis['revenue_growth']) !!}</p>
                  @else
                     <p class="ksr-kpi__value">{{ $kpis['revenue_growth'] !== null ? ($kpis['revenue_growth'] >= 0 ? '+' : '').number_format($kpis['revenue_growth'], 1).'%' : '—' }}</p>
                     <p class="ksr-kpi__note">Growth, last 12 months</p>
                  @endif
               </div>
            </div>
            <div class="col-6 col-lg-3">
               <div class="ksr-kpi">
                  <span class="ksr-kpi__icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></span>
                  <p class="ksr-kpi__label">Shipments handled</p>
                  <p class="ksr-kpi__value">{{ number_format($kpis['shipments_total']) }}</p>
                  <p class="ksr-kpi__note">All time</p>
               </div>
            </div>
         </div>

         <!-- Charts -->
         <div class="row g-4 mb-5">
            <div class="col-lg-6">
               <article class="ksr-chart-card">
                  <header class="ksr-chart-card__head">
                     <div>
                        <h3 class="ksr-chart-card__title">Client base</h3>
                        <p class="ksr-chart-card__sub">Registered clients at the end of each month, {{ $firstMonth }} &ndash; {{ $lastMonth }}</p>
                     </div>
                     {!! $growthBadge($kpis['clients_growth']) !!}
                  </header>
                  @include('site.partials.chart', [
                      'id' => 'clients-chart',
                      'type' => 'area',
                      'title' => 'Registered clients by month',
                      'points' => $clientPoints,
                      'axis' => fn (float $v): string => \App\Services\PublicInvestorMetrics::compactNumber($v),
                      'tone' => 'navy',
                      'valueHeading' => 'Clients',
                  ])
               </article>
            </div>
            <div class="col-lg-6">
               <article class="ksr-chart-card">
                  <header class="ksr-chart-card__head">
                     <div>
                        <h3 class="ksr-chart-card__title">Monthly revenue</h3>
                        <p class="ksr-chart-card__sub">
                           @if ($showRevenue)
                              Billed shipment value in US dollars, {{ $firstMonth }} &ndash; {{ $lastMonth }}
                           @else
                              Each month as a share of the best month, {{ $firstMonth }} &ndash; {{ $lastMonth }}
                           @endif
                        </p>
                     </div>
                     {!! $growthBadge($kpis['revenue_growth']) !!}
                  </header>
                  @include('site.partials.chart', [
                      'id' => 'revenue-chart',
                      'type' => 'bar',
                      'title' => $showRevenue ? 'Monthly revenue in US dollars' : 'Monthly revenue as a share of the best month',
                      'points' => $revenuePoints,
                      'axis' => $showRevenue ? fn (float $v): string => \App\Services\PublicInvestorMetrics::compactMoney($v) : fn (float $v): string => round($v).'%',
                      'tone' => 'primary',
                      'valueHeading' => $showRevenue ? 'Revenue (USD)' : 'Share of best month',
                  ])
               </article>
            </div>
         </div>
</div>
