@extends('site.layouts.app')

@section('title', 'Our Work')
@section('meta_description', 'Photographs of KASAROSE LOGISTICS at work — cargo consolidation, container loading, warehousing and door-to-door deliveries between the United States and Ghana.')

@section('content')
@php
// Real photographs of our own operations, carried over from the RDD Shipping site.
// These are the only genuine KASAROSE photos on this website — everything else in
// public/site/images is still template stock. Add new ones to public/site/images/gallery/ and list them here.
$gallery = [
    'shipping-01.jpg' => 'Loading a container for export',
    'shipping-02.jpg' => 'Moving a consignment across our warehouse floor',
    'shipping-03.jpg' => 'A wrapped pallet at the loading bay, ready to ship',
    'shipping-04.jpg' => 'A vehicle secured inside a container',
    'shipping-06.jpg' => 'A quad bike prepared for shipment',
    'shipping-07.jpg' => 'Oversized freight staged at the loading bay',
    'shipping-08.jpg' => 'Loading wrapped goods into the truck',
    'shipping-09.jpg' => 'One of our team with a consignment wrapped for transit',
    'shipping-10.jpg' => "A van loaded for the day's collections",
    'shipping-12.jpg' => 'Cartons palletised for onward shipping',
    'shipping-13.jpg' => 'Boxes moved through to the dispatch area',
    'shipping-14.jpg' => "A customer's carton packed and labelled",
    'shipping-17.jpg' => 'Delivering forwarded parcels to the door',
    'shipping-18.jpg' => 'Our warehouse',
    'shipping-19.jpg' => 'Inside our warehouse',
    'shipping-20.jpg' => 'Arriving at a residential delivery',
    'shipping-21.jpg' => 'A delivery van out on its round',
    'construction-1.jpg' => 'Reviewing plans on site for a property project',
];
// shipping-05/11/15/16/22/23 were dropped from this list: they're old promotional flyer
// graphics (ad copy, phone numbers, logos), not operational photography, and two
// (16, 22 — 22 is a near-duplicate of the excluded 23) carry the retired KasaBazaar
// logo/wordmark, which shouldn't appear publicly anymore. The files are left in
// images/gallery/ untouched.
@endphp

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Our Work</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Our Work</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <!-- Gallery Section Start -->
   <section class="gallery-sec sec-padding">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">From Our Warehouses</span>
            <h2 class="sec-title">Shipments We've Handled</h2>
            <p class="lead">Cargo consolidation, container loading, warehousing and door-to-door delivery
               between the United States and Ghana &mdash; photographed on our own sites.</p>
         </div>

         <div class="row g-4">
            @foreach ($gallery as $file => $caption)
               <div class="col-lg-4 col-sm-6">
                  <div class="portfolio-card position-relative h-100">
                     <div class="portfolio-img">
                        <img src="{{ asset('site/images/gallery/'.$file) }}"
                           alt="{{ $caption }}" class="img-fluid w-100" loading="lazy">
                     </div>
                     <div class="portfolio-info">
                        <h3 class="display-6 m-0">{{ $caption }}</h3>
                     </div>
                  </div>
               </div>
            @endforeach
         </div>
      </div>
   </section>
   <!-- Gallery Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Ready When You Are',
    'heading' => 'Let&rsquo;s move your shipment next.',
])

@endsection
