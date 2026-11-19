@extends('site.layouts.app')

@section('title', 'Investors')
@section('meta_description', 'KASAROSE LOGISTICS for investors — client growth and revenue across the USA to Ghana shipping corridor, from live company data.')

@php
   $usStates = array_values(array_filter(array_keys(config('website.service_states')), fn (string $s): bool => $s !== 'Ghana'));
   $market = collect(\App\Services\PublicWebsite::groupCompanies())->firstWhere('external', true);
   $email = config('website.investors.contact_email');
@endphp

@push('styles')
   <link rel="stylesheet" href="@siteAsset('css/investors.css')">
@endpush

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/'.config('website.page_banner')) }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Investors</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Investors</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <section class="ksr-investors sec-padding">
      <div class="container">
         <div class="sec-intro mx-auto text-center mb-5">
            <span class="sub-title2 fadeInUp">Investor Relations</span>
            <h2 class="sec-title">Growing the USA&ndash;Ghana shipping corridor.</h2>
            <p>{!! config('group.parent.name') !!} moves packages, vehicles and freight door to door from
               {{ count($usStates) }} US states to every region of Ghana. These figures come straight from the system we
               run the business on and refresh through the day.</p>
         </div>

         @include('site.partials.growth-charts', ['metrics' => $metrics, 'showRevenue' => $showRevenue])

         @if ($showRevenue && count($metrics['years']) > 1)
            <!-- Year on year -->
            <div class="ksr-years mb-5">
               <h3 class="ksr-years__title">Year by year</h3>
               @php $bestYear = max(array_column($metrics['years'], 'revenue') ?: [1]) ?: 1; @endphp
               <ul class="ksr-years__list">
                  @foreach ($metrics['years'] as $year)
                     <li class="ksr-years__row">
                        <span class="ksr-years__year">{{ $year['year'] }}@if ($year['partial'])<small> to date</small>@endif</span>
                        <span class="ksr-years__bar"><span style="width: {{ round($year['revenue'] / $bestYear * 100, 1) }}%"></span></span>
                        <span class="ksr-years__value">{{ \App\Services\PublicInvestorMetrics::compactMoney($year['revenue']) }}</span>
                        <span class="ksr-years__clients">+{{ number_format($year['new_clients']) }} clients</span>
                     </li>
                  @endforeach
               </ul>
            </div>
         @endif

         <!-- Why us -->
         <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-3">
               <div class="ksr-reason">
                  <i class="fa-solid fa-route" aria-hidden="true"></i>
                  <h3>One corridor, end to end</h3>
                  <p>We collect in the US, ship by air or sea, clear in Ghana and deliver to the door &mdash; one company owns every step.</p>
               </div>
            </div>
            <div class="col-md-6 col-lg-3">
               <div class="ksr-reason">
                  <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                  <h3>{{ count($usStates) }} US states</h3>
                  <p>Collections across {{ implode(', ', array_slice($usStates, 0, -1)) }} and {{ end($usStates) }}.</p>
               </div>
            </div>
            <div class="col-md-6 col-lg-3">
               <div class="ksr-reason">
                  <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i>
                  <h3>Built on our own platform</h3>
                  <p>Bookings, tracking, payments and alerts run on software we build ourselves, with a customer app on iOS and Android.</p>
               </div>
            </div>
            <div class="col-md-6 col-lg-3">
               <div class="ksr-reason">
                  <i class="fa-solid fa-sitemap" aria-hidden="true"></i>
                  <h3>A growing group</h3>
                  <p>{!! config('group.parent.name') !!} also carries deliveries for {{ $market['name'] ?? 'our marketplace' }}, the group&rsquo;s online marketplace.</p>
               </div>
            </div>
         </div>

         <!-- Contact -->
         <div class="ksr-invest-cta">
            <div>
               <h3>Talk to us about investing</h3>
               <p class="mb-0">Request our investor pack or arrange a call with the leadership team.</p>
            </div>
            <div class="ksr-invest-cta__actions">
               <a href="mailto:{{ $email }}?subject={{ rawurlencode('Investor enquiry') }}" class="btn btn-primary">Email Us <i class="fa fa-arrow-right"></i><span></span></a>
               <a href="{{ url('/investor') }}" class="ksr-invest-cta__link"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Investor sign-in</a>
            </div>
         </div>

         <p class="ksr-investors__disclaimer">Figures are unaudited management figures taken from our operating system and refreshed every few
            hours. Revenue is the billed value of shipments booked in each month, cancelled shipments excluded. Nothing on this
            page is an offer of shares or other securities.</p>
      </div>
   </section>

@push('scripts')
   <script src="@siteAsset('js/charts.js')" defer></script>
@endpush

@include('site.partials.cta', [
    'eyebrow' => 'Ready to Ship?',
    'heading' => 'Get a quote and we&rsquo;ll take it from there.',
])

@endsection
