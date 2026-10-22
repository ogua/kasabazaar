@extends('site.layouts.app')

@section('title', 'Our Companies')
@section('meta_description', 'The companies of KASAROSE LOGISTICS: KASAROSE LOGISTICS (freight, real estate, property management) and KASAROSE MARKET (ecommerce marketplace).')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
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

   <section class="sec-padding">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">One Group, Two Companies</span>
            <h2 class="sec-title">The KASAROSE Group</h2>
            <p class="lead">Two companies, one group &mdash; freight, real estate and property management,
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
                  <img src="{{ asset('site/images/gallery/shipping-01.jpg') }}" alt="Loading a KASAROSE container for export">
               </div>
            </div>
            <div class="col-lg-6 ps-lg-5 mt-5 mt-lg-0">
               <div class="about-content">
                  <span class="sub-title2 fadeInUp single">The Founding Company</span>
                  <h2 class="sec-title">KASAROSE LOGISTICS</h2>
                  <p class="text-primary fw-bold">Your Trusted Partner for Global Shipments.</p>
                  <p class="lead">Our founding company, incorporated in 2020. We
                     move freight door to door between the United States and Ghana &mdash; the business we ran for
                     years as Rose Door-to-Door Shipping &amp; Delivery &mdash; alongside real estate sales and
                     property management.</p>
                  <ul class="check">
                     <li>Air &amp; sea freight, door to door</li>
                     <li>Ecommerce shipments from Amazon, eBay, Walmart &amp; Best Buy</li>
                     <li>Real-time shipment tracking</li>
                     <li>Real estate sales &amp; investment guidance</li>
                     <li>Day-to-day property management</li>
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
               <div class="company-media company-media-logo round">
                  <img src="{{ asset('site/images/kasarosemarket-logo.png') }}" alt="KASAROSE MARKET logo">
               </div>
            </div>
            <div class="col-lg-6 pe-lg-5 mt-5 mt-lg-0">
               <div class="about-content">
                  <span class="sub-title2 fadeInUp single">Our Ecommerce Marketplace</span>
                  <h2 class="sec-title">KASAROSEMARKET</h2>
                  <p class="text-primary fw-bold">Ghana's multi-vendor marketplace, delivered.</p>
                  <p class="lead">KASAROSEMARKET is the group's retail arm &mdash; an online marketplace where vetted
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
