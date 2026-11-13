@extends('site.layouts.app')

@section('title', 'Our Companies')
@section('meta_description', 'The companies of KASAROSE LOGISTICS: KASAROSE LOGISTICS (door-to-door USA to Ghana shipping) and KASAROSE MARKET (ecommerce marketplace).')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/'.config('website.page_banner')) }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Our Companies</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Our Companies</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <section class="sec-padding pb-0">
      <div class="container">
         <div class="sec-intro mx-auto text-center mb-5">
            <span class="sub-title2 fadeInUp">One Group, Two Companies</span>
            <h2 class="sec-title">The KASAROSE Group</h2>
            <p class="lead">Two companies, one group &mdash; door-to-door shipping from the USA to Ghana,
               and an ecommerce marketplace.</p>
         </div>
      </div>
   </section>

   <!-- KASAROSE LOGISTICS -->
   <section class="about-sec2 overflow-hidden pt-0 sec-padding">
      <div class="container">
         <div class="row align-items-center">
            <div class="col-lg-6">
               <div class="company-media round">
                  <img src="{{ asset('site/images/hero/shipping-usa.jpg') }}" loading="lazy"
                     alt="KASAROSE LOGISTICS staff scanning and loading parcels at the US warehouse">
               </div>
            </div>
            <div class="col-lg-6 ps-lg-5 mt-5 mt-lg-0">
               <div class="about-content">
                  <span class="sub-title2 fadeInUp single">The Founding Company</span>
                  <h2 class="sec-title">KASAROSE LOGISTICS</h2>
                  <p class="text-primary fw-bold">Your Trusted Partner for Global Shipments.</p>
                  <p class="lead">Our founding company, incorporated in 2020. We
                     move freight door to door between the United States and Ghana &mdash; the business we ran for
                     years as Rose Door-to-Door Shipping &amp; Delivery &mdash; from pickup in the US to
                     the doorstep in Ghana.</p>
                  <ul class="check">
                     <li>Air &amp; sea freight, door to door</li>
                     <li>Ecommerce shipments from Amazon, eBay, Walmart &amp; Best Buy</li>
                     <li>Real-time shipment tracking</li>
                     <li>Pickup from homes &amp; businesses across our US operating states</li>
                     <li>Doorstep delivery across Ghana</li>
                  </ul>
                  <a href="{{ route('our-services') }}" class="btn btn-primary">View Our Services <i class="fa fa-arrow-right"></i><span></span></a>
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- KASAROSE MARKET -->
   <section class="about-sec2 overflow-hidden sec-padding bg-shade">
      <div class="container">
         <div class="row align-items-center flex-lg-row-reverse">
            <div class="col-lg-6">
               <div class="company-media round">
                  <img src="{{ asset('site/images/market/market-lifestyle.jpg') }}" loading="lazy"
                     alt="Two shoppers in Accra unpacking a KASAROSE MARKET delivery while browsing the app">
               </div>
            </div>
            <div class="col-lg-6 pe-lg-5 mt-5 mt-lg-0">
               <div class="about-content">
                  <div class="mb-4">
                     <img src="{{ asset('site/images/kasarosemarket-official.png') }}" alt="" style="max-width: 200px; height: auto;">
                  </div>
                  <span class="sub-title2 fadeInUp single">Our Ecommerce Marketplace</span>
                  <h2 class="sec-title">KASAROSE MARKET</h2>
                  <p class="text-primary fw-bold">Ghana's multi-vendor marketplace, delivered.</p>
                  <p class="lead">KASAROSE MARKET is the group's retail arm &mdash; an online marketplace where vetted
                     Ghanaian vendors sell direct to shoppers nationwide. Payment is held until delivery, and orders
                     move on the group's own logistics network, so a small seller can reach the whole country without
                     owning a single vehicle.</p>
                  <ul class="check">
                     <li>Thousands of products from vetted local vendors</li>
                     <li>One cart across multiple vendors, one payment</li>
                     <li>Nationwide delivery carried by KASAROSE LOGISTICS</li>
                  </ul>
                  <a href="{{ $marketUrl }}" target="_blank" rel="noopener" class="btn btn-primary">Visit
                     kasarosemarket.com <i class="fa fa-arrow-right"></i><span></span></a>
               </div>
            </div>
         </div>
      </div>
   </section>

@include('site.partials.cta', [
    'eyebrow' => 'Questions?',
    'heading' => 'Not sure which company can help? Just ask us.',
    'buttons' => [['label' => 'Contact Us', 'href' => route('contact-us'), 'style' => 'primary']],
])

@endsection
