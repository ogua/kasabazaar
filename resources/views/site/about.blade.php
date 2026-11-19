@extends('site.layouts.app')

@section('title', 'About Us')
@section('meta_description', 'About KASAROSE LOGISTICS — door-to-door shipping from the USA to Ghana, the people behind it, and our sister company KASAROSE MARKET.')

@php
// Photography for this page, relative to public/site. Swap a file here to change it everywhere on the page.
$photos = [
    'pickup' => 'images/hero/pickup-usa.jpg',
    'warehouse' => 'images/hero/shipping-usa.jpg',
    'delivery' => 'images/hero/delivery-ghana.jpg',
    'family' => 'images/hero/delivery-ghana-family.jpg',
    'usTeam' => 'images/team/us-warehouse-team.jpg',
];
$journey = [
    ['step' => '01', 'title' => 'Collected in the USA', 'photo' => 'pickup', 'text' => 'Our uniformed crew picks up from homes and businesses across seven states, or you drop off at our US facility.', 'alt' => 'A KASAROSE LOGISTICS courier collecting a parcel from a home in the United States'],
    ['step' => '02', 'title' => 'Scanned &amp; shipped', 'photo' => 'warehouse', 'text' => 'Every box is checked, labelled and scanned at the warehouse, then flown or shipped to Ghana by air or sea.', 'alt' => 'KASAROSE LOGISTICS warehouse staff scanning and loading labelled boxes'],
    ['step' => '03', 'title' => 'Delivered in Ghana', 'photo' => 'delivery', 'text' => 'Our Ghana team brings it to the recipient\'s door and gets a signature on handover &mdash; no port queues.', 'alt' => 'A KASAROSE LOGISTICS courier handing a parcel to a customer at her gate in Ghana'],
];
$stats = [
    ['value' => 7, 'label' => 'US states we collect from'],
    ['value' => 2, 'label' => 'Countries, one door-to-door service'],
    ['value' => 4, 'label' => 'Core shipping services'],
    ['value' => count($groupCompanies), 'label' => 'Companies in the group'],
];
$values = [
    ['icon' => 'fa-truck-fast', 'title' => 'Speed and Reliability', 'text' => 'Efficient routing, real-time tracking and contingency planning keep delays to a minimum. Our reputation rests on a straightforward pick&ndash;package&ndash;ship&ndash;deliver approach.'],
    ['icon' => 'fa-tags', 'title' => 'Cost Efficiency', 'text' => 'Cost-effective options without compromising on the quality of the service. Our rates are affordable and we are happy to be compared.'],
    ['icon' => 'fa-comments', 'title' => 'Transparency', 'text' => 'Status updates by SMS, email and the app, and a tracking page that works for anyone with the number &mdash; never silence between collection and delivery.'],
    ['icon' => 'fa-arrows-up-down-left-right', 'title' => 'Flexibility', 'text' => 'Our service scales with you, from a single barrel or box to a full container, and from one-off gifts to regular business freight.'],
];
$team = [
    ['name' => 'J.W. Adel', 'role' => 'Director', 'photo' => 'images/team/jw-adel.jpg'],
    ['name' => 'Rose N. Adel', 'role' => 'Chief Executive Officer', 'photo' => 'images/team/rose-n-adel.jpg'],
];
$states = array_values(array_diff(array_keys(config('website.service_states', [])), ['Ghana']));
@endphp

@push('styles')
   <link rel="stylesheet" href="@siteAsset('css/about.css')">
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
                  <h1 class="display-2 text-info">About Us</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">About Us</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <!-- Intro Section Start -->
   <section class="ksr-about-intro sec-padding">
      <div class="container">
         <div class="row align-items-center gy-5">
            <div class="col-lg-6">
               <div class="ksr-collage">
                  <img class="ksr-collage__main" src="{{ asset('site/'.$photos['pickup']) }}"
                     alt="A KASAROSE LOGISTICS courier collecting a parcel in the United States">
                  <img class="ksr-collage__inset" src="{{ asset('site/'.$photos['delivery']) }}"
                     alt="A KASAROSE LOGISTICS courier delivering a parcel in Ghana" loading="lazy">
                  <div class="ksr-collage__badge">
                     <span>USA <i class="fa-solid fa-plane" aria-hidden="true"></i> GHANA</span>
                     <strong>Door to door</strong>
                  </div>
               </div>
            </div>
            <div class="col-lg-6 ps-lg-5">
               <span class="sub-title2 fadeInUp single">Who We Are</span>
               <h2 class="sec-title">Twenty years of moving things people care about.</h2>
               <p class="lead">KASAROSE LOGISTICS ships from a door in the United States to a door in Ghana &mdash;
                  by air or sea, tracked the whole way.</p>
               <p>Registered and incorporated in 2020, we became a household name in Ghana and among African
                  communities in the United States &mdash; particularly in Michigan, Indiana, New York and
                  Kentucky &mdash; as a freight forwarding and home delivery service. For most of that time we
                  traded as Rose Door-to-Door Shipping &amp; Delivery; today that work is carried out under the
                  KASAROSE LOGISTICS name.</p>
               <div class="d-sm-flex align-items-center gap-5 mt-4">
                  <a href="{{ route('quote') }}" class="btn btn-primary">Request a Quote <i class="fa fa-arrow-right"></i><span></span></a>
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

         <div class="ksr-stats">
            @foreach ($stats as $stat)
            <div class="ksr-stat">
               <span class="ksr-stat__value purecounter" data-purecounter-end="{{ $stat['value'] }}">{{ $stat['value'] }}</span>
               <span class="ksr-stat__label">{{ $stat['label'] }}</span>
            </div>
            @endforeach
         </div>
      </div>
   </section>
   <!-- Intro Section End -->

   <!-- Journey Section Start -->
   <section class="ksr-journey sec-padding bg-shade">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">How We Work</span>
            <h2 class="sec-title">One door in America. Another in Ghana.</h2>
            <p>The global supply chain has become complex. We keep the part that matters simple.</p>
         </div>
         <div class="row g-4">
            @foreach ($journey as $stage)
            <div class="col-lg-4 col-md-6">
               <article class="ksr-stage">
                  <div class="ksr-stage__photo">
                     <img src="{{ asset('site/'.$photos[$stage['photo']]) }}" alt="{{ $stage['alt'] }}" loading="lazy">
                     <span class="ksr-stage__step">{{ $stage['step'] }}</span>
                  </div>
                  <div class="ksr-stage__body">
                     <h3 class="display-6">{!! $stage['title'] !!}</h3>
                     <p class="mb-0">{!! $stage['text'] !!}</p>
                  </div>
               </article>
            </div>
            @endforeach
         </div>
      </div>
   </section>
   <!-- Journey Section End -->

   <!-- Mission Section Start -->
   <section class="ksr-mission">
      <div class="ksr-mission__photo">
         <img src="{{ asset('site/'.$photos['family']) }}"
            alt="A KASAROSE LOGISTICS courier handing a parcel to a mother and daughter at their door in Ghana" loading="lazy">
      </div>
      <div class="container">
         <div class="row">
            <div class="col-lg-6">
               <div class="ksr-mission__copy">
                  <span class="sub-title2 fadeInUp single">What Drives Us</span>
                  <h2 class="sec-title text-white">Packed in America. Opened with a smile in Ghana.</h2>
                  <div class="ksr-mission__item">
                     <span class="ksr-mission__icon"><i class="fa-solid fa-bullseye" aria-hidden="true"></i></span>
                     <div>
                        <h3 class="display-6 text-white">Our Mission</h3>
                        <p>To make sending goods between the USA and Ghana simple and dependable for the people and
                           businesses we serve &mdash; picked up at one door, handed over at the other.</p>
                     </div>
                  </div>
                  <div class="ksr-mission__item">
                     <span class="ksr-mission__icon"><i class="fa-solid fa-binoculars" aria-hidden="true"></i></span>
                     <div>
                        <h3 class="display-6 text-white">Our Vision</h3>
                        <p>To keep growing KASAROSE LOGISTICS' reach across the United States and Ghana, giving
                           clients more ways to ship, shop and stay connected across the Atlantic.</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Mission Section End -->

   <!-- Coverage Section Start -->
   <section class="ksr-coverage sec-padding">
      <div class="container">
         <div class="row align-items-center gy-5">
            <div class="col-lg-5">
               <span class="sub-title2 fadeInUp single">Where We Operate</span>
               <h2 class="sec-title">Operating States &amp; Destinations</h2>
               <p class="lead mb-0">We collect from several states across the United States and ship to Ghana and
                  anywhere in the USA.</p>
            </div>
            <div class="col-lg-7">
               <div class="ksr-coverage__grid">
                  <div class="ksr-coverage__card">
                     <h3 class="display-6"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> We collect from</h3>
                     <ul class="ksr-chips">
                        @foreach ($states as $state)
                        <li>{{ $state }}</li>
                        @endforeach
                     </ul>
                  </div>
                  <div class="ksr-coverage__card ksr-coverage__card--dark">
                     <h3 class="display-6 text-white"><i class="fa-solid fa-globe" aria-hidden="true"></i> We deliver to</h3>
                     <ul class="ksr-chips">
                        <li>Anywhere in Ghana</li>
                        <li>Any state in the USA</li>
                     </ul>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Coverage Section End -->

   <!-- Values Section Start -->
   <section class="ksr-values sec-padding bg-shade">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">Why Choose Us</span>
            <h2 class="sec-title">We Understand What Our Customers Need</h2>
         </div>
         <div class="row g-4">
            @foreach ($values as $value)
            <div class="col-lg-3 col-sm-6">
               <div class="ksr-value">
                  <span class="ksr-value__icon"><i class="fa-solid {{ $value['icon'] }}" aria-hidden="true"></i></span>
                  <h3 class="display-6">{{ $value['title'] }}</h3>
                  <p class="mb-0">{!! $value['text'] !!}</p>
               </div>
            </div>
            @endforeach
         </div>
      </div>
   </section>
   <!-- Values Section End -->

   <!-- Team Section Start -->
   <section class="ksr-team sec-padding">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">Leadership</span>
            <h2 class="sec-title">The People Behind Your Shipment</h2>
            <p>Our staff are experienced in freight forwarding and home delivery, and our shipping experts are
               there to anticipate and meet your needs whenever they arise.</p>
         </div>
         <div class="row g-4 justify-content-center">
            @foreach ($team as $member)
            <div class="col-lg-4 col-sm-6">
               <figure class="ksr-member">
                  <div class="ksr-member__photo">
                     <img src="{{ asset('site/'.$member['photo']) }}" alt="{{ $member['name'] }}, {{ $member['role'] }}" loading="lazy">
                  </div>
                  <figcaption class="ksr-member__caption">
                     <h3 class="display-6 mb-1">{{ $member['name'] }}</h3>
                     <p class="mb-0">{{ $member['role'] }}</p>
                  </figcaption>
               </figure>
            </div>
            @endforeach
         </div>

         <div class="ksr-us-team row align-items-center g-0">
            <div class="col-md-5">
               <img class="ksr-us-team__photo" src="{{ asset('site/'.$photos['usTeam']) }}"
                  alt="Two members of the KASAROSE LOGISTICS team at the US warehouse with a branded shipping box"
                  width="800" height="984" loading="lazy">
            </div>
            <div class="col-md-7">
               <div class="ksr-us-team__copy">
                  <span class="sub-title2 fadeInUp single">On the US Side</span>
                  <h3 class="display-6">The hands that pack your boxes</h3>
                  <p>Our US warehouse team receives every drop-off and collection, checks and labels each box,
                     and gets it ready for the trip to Ghana &mdash; so it arrives the way you sent it.</p>
                  <a href="{{ route('quote') }}" class="btn btn-primary">Ship With Us <i class="fa fa-arrow-right"></i><span></span></a>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Team Section End -->

   <!-- Group Section Start -->
   <section class="ksr-group sec-padding bg-shade">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">The Group</span>
            <h2 class="sec-title">One Group. Two Ways We Can Help.</h2>
            <p>Whichever of our companies you work with, you get the same commitment to reliable, honest service.</p>
         </div>
         <div class="row g-4 justify-content-center">
            @foreach ($groupCompanies as $company)
            <div class="col-lg-5 col-md-6">
               <div class="ksr-company">
                  @if ($company['logo'])
                  <img class="ksr-company__logo" src="{{ asset('site/'.$company['logo']) }}" alt="{{ $company['name'] }}" loading="lazy">
                  @endif
                  <h3 class="display-6">{{ $company['name'] }}</h3>
                  <p>{{ $company['tagline'] }}</p>
                  <a class="link-btn mt-auto" href="{{ $company['url'] }}" @if ($company['external']) target="_blank" rel="noopener" @endif>
                     {{ $company['url_label'] }} <i class="fa fa-arrow-right"></i></a>
               </div>
            </div>
            @endforeach
         </div>
      </div>
   </section>
   <!-- Group Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Work With Us',
    'heading' => 'Whatever you need moved, we can help.',
])

@endsection
