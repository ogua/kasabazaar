@extends('site.layouts.app')

@section('title', 'Home')
@section('meta_description', 'KASAROSE LOGISTICS — door-to-door air and sea freight between the US and Ghana, ecommerce package forwarding, shipment tracking and doorstep delivery in Ghana.')

@section('content')

@php
$heroSlides = config('website.hero_slides');
@endphp
@push('styles')
   <link rel="stylesheet" href="@siteAsset('css/hero.css')">
   <link rel="preload" as="image" href="{{ asset('site/'.$heroSlides[0]['image']) }}">
@endpush
@push('scripts')
   <script src="@siteAsset('js/hero.js')"></script>
@endpush

   <!-- Hero Section Start -->
   <section class="ksr-hero" aria-roledescription="carousel" aria-label="Ship from the USA to Ghana with KASAROSE LOGISTICS">
      <div class="ksr-hero__slider swiper">
         <div class="swiper-wrapper">
            @foreach ($heroSlides as $slide)
            <div class="swiper-slide ksr-slide ksr-slide--{{ $slide['layout'] }}" role="group"
               aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}: {{ $slide['step'] }}">
               <div class="ksr-slide__art">
                  <img src="{{ asset('site/'.$slide['image']) }}" alt="{{ $slide['alt'] }}"
                     @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
               </div>
               <div class="ksr-slide__accent" aria-hidden="true"></div>
               <div class="ksr-slide__panel">
                  <div class="ksr-slide__copy">
                     <span class="ksr-route">USA <i class="fa-solid fa-plane" aria-hidden="true"></i> GHANA</span>
                     <p class="ksr-eyebrow">{!! $slide['eyebrow'] !!}</p>
                     <!-- Only the first slide carries the page's h1. -->
                     @if ($loop->first)
                     <h1 class="ksr-title">{!! $slide['heading'] !!}</h1>
                     @else
                     <h2 class="ksr-title">{!! $slide['heading'] !!}</h2>
                     @endif
                     <p class="ksr-text">{!! $slide['text'] !!}</p>
                     <div class="ksr-actions">
                        <a href="{{ route($slide['cta']['route']) }}" class="btn btn-hover btn-primary">{{ $slide['cta']['label'] }}
                           <i class="fa fa-arrow-right"></i><span></span></a>
                        @if ($slide['cta']['route'] === 'our-tracking')
                        <a href="{{ route('quote') }}" class="btn btn-outline">Get a Quote <i class="fa fa-arrow-right"></i><span></span></a>
                        @else
                        <a href="{{ route('our-tracking') }}" class="btn btn-outline">Track a Shipment <i class="fa fa-arrow-right"></i><span></span></a>
                        @endif
                     </div>
                     @if ($slide['layout'] === 'poster')
                     <p class="ksr-phones">
                        <span><i class="fa-solid fa-phone" aria-hidden="true"></i>USA
                           <a href="tel:{!! config('website.contact.phone_us_2_tel') !!}">{!! config('website.contact.phone_us_2') !!}</a></span>
                        <span><i class="fa-solid fa-phone" aria-hidden="true"></i>Ghana
                           <a href="tel:{!! config('website.contact.phone_gh_2_tel') !!}">{!! config('website.contact.phone_gh_2') !!}</a></span>
                     </p>
                     @endif
                  </div>
               </div>
            </div>
            @endforeach
         </div>
      </div>

      <nav class="ksr-steps" aria-label="How your shipment travels">
         <ol class="ksr-steps__list">
            @foreach ($heroSlides as $slide)
            <li>
               <button type="button" class="ksr-step{{ $loop->first ? ' is-active' : '' }}">
                  <span class="ksr-step__bar" aria-hidden="true"><i></i></span>
                  <span class="ksr-step__num">{{ sprintf('%02d', $loop->iteration) }}</span>
                  <span>
                     <span class="ksr-step__label">{{ $slide['step'] }}</span>
                     <span class="ksr-step__note">{!! $slide['step_note'] !!}</span>
                  </span>
               </button>
            </li>
            @endforeach
         </ol>
         <div class="ksr-controls">
            <button type="button" class="ksr-prev" aria-label="Previous slide"><i class="fa fa-arrow-left" aria-hidden="true"></i></button>
            <button type="button" class="ksr-toggle" aria-label="Pause slideshow" aria-pressed="false">
               <i class="fa-solid fa-pause" aria-hidden="true"></i><i class="fa-solid fa-play" aria-hidden="true"></i></button>
            <button type="button" class="ksr-next" aria-label="Next slide"><i class="fa fa-arrow-right" aria-hidden="true"></i></button>
         </div>
      </nav>
   </section>
   <!-- Hero Section End -->

   <!-- Quick Track Section Start -->
   <section class="bg-shade py-5">
      <div class="container">
         <div class="row align-items-center g-4">
            <div class="col-lg-4">
               <h2 class="display-4 mb-1">Track Your Shipment</h2>
               <p class="mb-0">Enter the tracking number or reference from your invoice.</p>
            </div>
            <div class="col-lg-8">
               <form method="get" action="{{ route('our-tracking') }}" class="tracking-form d-sm-flex m-0">
                  <label for="home-track" class="visually-hidden">Tracking number or shipment reference</label>
                  <input type="text" id="home-track" name="ref" class="form-control"
                     placeholder="Tracking number or reference" required>
                  <button type="submit" class="btn btn-primary">Track <i class="fa fa-arrow-right"></i><span></span></button>
               </form>
            </div>
         </div>
      </div>
   </section>
   <!-- Quick Track Section End -->

   <!-- Services Section Start -->
   <div class="services-sec2 sec-padding overflow-hidden bg-cover jarallax" data-jarallax data-speed=".2">
      <img src="{{ asset('site/images/service2-bg.jpg') }}" alt="parallax" class="jarallax-img">
      <div class="container ct-container p-0">
         <div class="row align-items-center serv-header">
            <div class="col-lg-8">
               <div class="sec-intro">
                  <span class="sub-title2 fadeInUp single">What We Do</span>
                  <h2 class="sec-title">Services Across KASAROSE LOGISTICS</h2>
               </div>
            </div>
            <div class="col-lg-4">
               <div class="header-slider-nav">
                  <div class="swiper-button-next"></div>
                  <div class="swiper-button-prev"></div>
               </div>
            </div>
         </div>
         <div class="row">
            <div class="col-lg-12">
               <div class="service-slider2 swiper">
                  <div class="swiper-wrapper">
                     @foreach ($siteServices as $slug => $svc)
                     <div class="swiper-slide">
                        <div class="service-card2 round bg-info">
                           <div class="serv-thumb" style="background-image: url('{{ asset('site/'.$svc['thumb']) }}');">
                              <span class="icon round-2 text-white">
                                 <i class="fa-solid {!! $svc['icon'] !!} fa-2x" aria-hidden="true"></i>
                              </span>
                           </div>
                           <h3 class="display-4"><a href="{{ route('our-services') }}#{!! $slug !!}">{!! $svc['label'] !!}</a></h3>
                           <p>{!! $svc['tagline'] !!}</p>
                           <div class="service-footer border-top">
                              <a class="custom-btn" href="{{ route('our-services') }}#{!! $slug !!}"><span class="icon rounded-3"><i
                                       class="fa fa-arrow-right"></i></span>View Details</a>
                           </div>
                        </div>
                     </div>
                     @endforeach
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
   <!-- Services Section End -->

   <!-- About Section Start -->
   <section class="ksr-about sec-padding overflow-hidden">
      <div class="container">
         <div class="row align-items-center gy-5">
            <div class="col-lg-6">
               <div class="ksr-about__media">
                  <figure class="ksr-about__photo ksr-about__photo--us">
                     <img src="{{ asset('site/images/hero/pickup-usa.jpg') }}" loading="lazy"
                        alt="A KASAROSE LOGISTICS courier collecting a parcel from a home in the United States">
                     <figcaption><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Picked up in the USA</figcaption>
                  </figure>
                  <figure class="ksr-about__photo ksr-about__photo--gh">
                     <img src="{{ asset('site/images/hero/delivery-ghana.jpg') }}" loading="lazy"
                        alt="A KASAROSE LOGISTICS courier handing a parcel to a customer at her gate in Ghana">
                     <figcaption><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Delivered in Ghana</figcaption>
                  </figure>
                  <span class="ksr-about__route" aria-hidden="true"><i class="fa-solid fa-plane"></i></span>
               </div>
            </div>
            <div class="col-lg-6 ps-lg-5">
               <span class="sub-title2 fadeInUp single">About KASAROSE LOGISTICS</span>
               <h2 class="sec-title">One Crew. Two Countries. Every Door.</h2>
               <p class="lead">As Rose Door-to-Door Shipping we became a household name in Ghana and among
                  African communities in the US. As KASAROSE LOGISTICS we still look after the whole journey,
                  from your pickup to their doorstep.</p>

               <ol class="ksr-about__journey">
                  <li>
                     <span class="ksr-about__icon"><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i></span>
                     <div>
                        <h3 class="h5">We collect</h3>
                        <p>From homes and businesses across Michigan, Illinois, Indiana, New York, New Jersey,
                           Kentucky and Ohio.</p>
                     </div>
                  </li>
                  <li>
                     <span class="ksr-about__icon"><i class="fa-solid fa-plane-departure" aria-hidden="true"></i></span>
                     <div>
                        <h3 class="h5">We ship</h3>
                        <p>Air freight in {!! $deliveryTimeframes[0]['estimate'] !!} or ocean freight in
                           {!! $deliveryTimeframes[1]['estimate'] !!}, tracked by SMS, email and the app.</p>
                     </div>
                  </li>
                  <li>
                     <span class="ksr-about__icon"><i class="fa-solid fa-door-open" aria-hidden="true"></i></span>
                     <div>
                        <h3 class="h5">We deliver</h3>
                        <p>Our Ghana crew hands it over at your door and confirms delivery.</p>
                     </div>
                  </li>
               </ol>

               <div class="d-sm-flex align-items-center gap-5 mt-4">
                  <a href="{{ route('about-us') }}" class="btn btn-primary">Our Story <i class="fa fa-arrow-right"></i><span></span></a>
                  <div class="quick-call d-flex align-items-center mt-4 mt-sm-0">
                     <span class="bg-dark icon-lg rounded-circle text-info"><i class="fa-solid fa-phone-volume"></i></span>
                     <div class="conn-txt ms-3">
                        <p class="m-0">Call Us Any Time:</p>
                        <a class="h6 text-primary" href="tel:{!! config('website.contact.phone_us_1_tel') !!}">{!! config('website.contact.phone_us_1') !!}</a>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- About Section End -->

   <!-- App Section Start -->
   <section class="pb-5">
      <div class="container">
         @include('site.partials.app-promo', [
             'heading' => 'The KASAROSE app is here.',
             'text' => 'Track your shipments and get status updates from your phone &mdash; free on iPhone and Android.',
         ])
      </div>
   </section>
   <!-- App Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Get Started',
    'heading' => 'Ready to ship from the USA to Ghana?',
])

@endsection
