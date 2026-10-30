@extends('site.layouts.app')

@section('title', 'Home')
@section('meta_description', 'KASAROSE LOGISTICS — door-to-door air and sea freight between the US and Ghana, ecommerce package forwarding, shipment tracking, real estate and property management.')

@section('content')

   <!-- Slider Section Start -->
   <section class="slider-sec2 position-relative">
      <a href="{{ route('our-companies') }}" class="slide-cta">Our Group of Companies</a>
      <div class="hero-slider2 swiper">
         <div class="swiper-wrapper">
            <div class="swiper-slide">
               <div class="slide-item position-relative">
                  <div class="item-bg" style="background-image: url('{{ asset('site/images/slider1.jpg') }}');"></div>
                  <div class="slide-content">
                     <div class="parallax-txt">
                        <img src="{{ asset('site/images/slide-bg.png') }}" alt="" class="img-fluid">
                     </div>
                     <span class="sub-title h5">KASAROSE LOGISTICS</span>
                     <h1 class="display-1 fw-bold">Import &amp; export shipping, real estate and property management.</h1>
                     <p>From door-to-door international shipping to real estate and property management, KASAROSE LOGISTICS
                        Group brings dependable, end-to-end service to every client we serve.</p>
                     <div class="d-sm-flex slide-cta gap-4">
                        <a href="{{ route('quote') }}" class="btn btn-hover btn-primary">Get a Quote <i
                              class="fa fa-arrow-right"></i>
                           <span></span>
                        </a>
                        <a href="{{ route('our-tracking') }}" class="btn btn-outline">Track Shipment <i class="fa fa-arrow-right"></i>
                           <span></span>
                        </a>
                     </div>
                  </div>
                  <img src="{{ asset('site/images/slide-anim.png') }}" class="abs-img start-0 bottom-0 d-none d-sm-block" alt="">
               </div>
            </div> <!-- Slide End -->

            <div class="swiper-slide">
               <div class="slide-item position-relative">
                  <div class="item-bg" style="background-image: url('{{ asset('site/images/slider3.jpg') }}');"></div>
                  <div class="slide-content">
                     <div class="parallax-txt">
                        <img src="{{ asset('site/images/slide-bg.png') }}" alt="" class="img-fluid">
                     </div>
                     <span class="sub-title h5">One Group, Multiple Strengths</span>
                     <!-- h2, not h1: only one h1 per page, and the first slide already carries it. -->
                     <h2 class="display-1 fw-bold">One group moving goods, people and property forward.</h2>
                     <p>Our own freight network and the KASAROSE MARKET marketplace &mdash; KASAROSE LOGISTICS
                        serves clients across the United States, Ghana, and beyond.</p>
                     <div class="d-sm-flex slide-cta gap-4">
                        <a href="{{ route('our-companies') }}" class="btn btn-hover btn-primary">Our Companies <i
                              class="fa fa-arrow-right"></i>
                           <span></span>
                        </a>
                        <a href="{{ route('contact-us') }}" class="btn btn-outline">Contact Us <i class="fa fa-arrow-right"></i>
                           <span></span>
                        </a>
                     </div>
                  </div>
                  <img src="{{ asset('site/images/slide-anim.png') }}" class="abs-img start-0 bottom-0 d-none d-sm-block" alt="">
               </div>
            </div> <!-- Slide End -->
         </div>
      </div>
      <div class="custom-nav">
         <div class="swiper-button-next"></div>
         <div class="swiper-button-prev"></div>
      </div>
   </section>
   <!-- Slider Section End -->

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

   <!-- App Section Start -->
   <section class="pt-5">
      <div class="container">
         @include('site.partials.app-promo', [
             'heading' => 'The KASAROSE app is here.',
             'text' => 'Track your shipments and get status updates from your phone &mdash; free on iPhone and Android.',
         ])
      </div>
   </section>
   <!-- App Section End -->

   <!-- About Section Start -->
   <section class="about-sec2 overflow-hidden">
      <img src="{{ asset('site/images/container.png') }}" alt="container" class="position-absolute top-0">
      <div class="container">
         <div class="row align-items-end">
            <div class="col-xl-6 col-sm-8 ms-sm-auto">
               <div class="about-media-box position-relative">
                  <div class="ab-main-img">
                     <img src="{{ asset('site/images/about-m.jpg') }}" class="img-fluid" alt="">
                     <div class="experien-stat">
                        <p class="text-info m-0"> <span class="purecounter" data-purecounter-end="2">2</span> Group
                           Companies</p>
                     </div>
                     <div class="about-sm">
                        <img class="img-fluid" src="{{ asset('site/images/about-m2.png') }}" alt="">
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-xl-6 ps-xl-4">
               <div class="about-content">
                  <span class="sub-title2 fadeInUp single">About KASAROSE LOGISTICS</span>
                  <h2 class="sec-title">A Group Built on Trade, Property &amp; Mobility</h2>
                  <p class="lead">KASAROSE LOGISTICS operates across international trade, real estate,
                     property management and freight logistics &mdash; serving clients in the United
                     States and Ghana.</p>
                  <ul class="check">
                     <li>Door-to-Door Air &amp; Sea Freight</li>
                     <li>Ecommerce Package Forwarding &amp; Tracking</li>
                     <li>Real Estate &amp; Property Management</li>
                  </ul>
                  <div class="d-sm-flex align-items-center about-cta gap-5">
                     <a href="{{ route('about-us') }}" class="btn btn-primary">About More <i class="fa fa-arrow-right"></i><span></span> </a>

                     <div class="quick-call d-flex align-items-center">
                        <span class="bg-dark icon-lg rounded-circle text-info"><i
                              class="fa-solid fa-phone-volume"></i></span>
                        <div class="conn-txt ms-3">
                           <p class="m-0">Call Us Any Time:</p>
                           <a class="h6 text-primary" href="tel:{!! config('website.contact.phone_us_1_tel') !!}">{!! config('website.contact.phone_us_1') !!}</a>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="about-footer d-sm-flex align-items-center gap-5 bg-shade round mt-5">
                  <div class="af-item d-flex align-items-start gap-4">
                     <img src="{{ asset('site/images/af-1.png') }}" alt="import export">
                     <div class="af-info">
                        <h3 class="h5">Import &amp; Export</h3>
                        <p>Reliable door-to-door shipping for individuals and businesses.</p>
                     </div>
                  </div>
                  <div class="af-item d-flex align-items-start gap-4">
                     <img src="{{ asset('site/images/af-2.png') }}" alt="real estate">
                     <div class="af-info">
                        <h3 class="h5">Real Estate</h3>
                        <p>Property sales and management guidance you can trust.</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- About Section End -->

   <div class="text-slide-sec sec-padding overflow-hidden">
      <div class="marque-active">
         <div class="text-slide-wrap">
            <div class="text-item">
               <img src="{{ asset('site/images/star-1.png') }}" alt="">
               <h3 class="display-1">Import &amp; Export</h3>
            </div>
            <div class="text-item">
               <img src="{{ asset('site/images/star-1.png') }}" alt="">
               <h3 class="display-1">Real Estate</h3>
            </div>
            <div class="text-item">
               <img src="{{ asset('site/images/star-1.png') }}" alt="">
               <h3 class="display-1">Property Management</h3>
            </div>
            <div class="text-item">
               <img src="{{ asset('site/images/star-1.png') }}" alt="">
               <h3 class="display-1">Logistics</h3>
            </div>
            <div class="text-item">
               <img src="{{ asset('site/images/star-1.png') }}" alt="">
               <h3 class="display-1">Mobility</h3>
            </div>
         </div>
      </div>
   </div>

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

   <!-- Our Companies Section Start -->
   <section class="sec-padding bg-shade">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">One Group</span>
            <h2 class="sec-title">Our Group of Companies</h2>
         </div>
         <div class="row gy-4">
            @foreach ($groupCompanies as $company)
            <div class="col-lg-6">
               <div class="bg-white round p-4 h-100 d-flex flex-column">
                  <h3 class="display-5">{{ $company['name'] }}</h3>
                  <p class="text-primary fw-bold">{{ $company['tagline'] }}</p>
                  <p class="flex-grow-1">{{ $company['description'] }}</p>
                  <a href="{{ $company['url'] }}" class="btn btn-primary mt-3"
                     {!! $company['external'] ? 'target="_blank" rel="noopener"' : '' !!}>
                     {{ $company['url_label'] }} <i class="fa fa-arrow-right"></i><span></span>
                  </a>
               </div>
            </div>
            @endforeach
         </div>
      </div>
   </section>
   <!-- Our Companies Section End -->

   <!-- Choose Section Start -->
   <section class="choose-sec2 bg-cover sec-padding" style="background-image: url('{{ asset('site/images/choose-bg.png') }}');">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">Why Choose Us</span>
            <h2 class="sec-title">What Sets KASAROSE LOGISTICS Apart</h2>
         </div>
         <div class="row g-1">
            <div class="col-lg-3 col-sm-6">
               <div class="single-choose fadeInUp">
                  <h3 class="display-6 fw-bold">Door-to-Door Shipping</h3>
                  <span class="icon bg-primary text-white">
                     <i class="fa-solid fa-truck-ramp-box fa-xl" aria-hidden="true"></i>
                  </span>
               </div>
               <div class="single-choose fadeInUp">
                  <h3 class="display-6 fw-bold">Real-Time Tracking</h3>
                  <span class="icon bg-primary text-white">
                     <i class="fa-solid fa-location-crosshairs fa-xl" aria-hidden="true"></i>
                  </span>
               </div>
               <div class="single-choose fadeInUp">
                  <h3 class="display-6 fw-bold">Cost Efficiency</h3>
                  <span class="icon bg-primary text-white">
                     <i class="fa-solid fa-tags fa-xl" aria-hidden="true"></i>
                  </span>
               </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
               <div class="choose-media">
                  <img src="{{ asset('site/images/choose-main.png') }}" alt="" class="img-fluid">
               </div>
            </div>
            <div class="col-lg-3 col-sm-6 reverse-choose">
               <div class="single-choose fadeInUp">
                  <h3 class="display-6 fw-bold">Transparent Communication</h3>
                  <span class="icon bg-primary text-white">
                     <i class="fa-solid fa-comments fa-xl" aria-hidden="true"></i>
                  </span>
               </div>
               <div class="single-choose fadeInUp">
                  <h3 class="display-6 fw-bold">Flexible &amp; Scalable</h3>
                  <span class="icon bg-primary text-white">
                     <i class="fa-solid fa-arrows-up-down-left-right fa-xl" aria-hidden="true"></i>
                  </span>
               </div>
               <div class="single-choose fadeInUp">
                  <h3 class="display-6 fw-bold">Real Estate &amp; Property</h3>
                  <span class="icon bg-primary text-white">
                     <i class="fa-solid fa-building fa-xl" aria-hidden="true"></i>
                  </span>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Choose Section End -->

   <!-- Operation Section Start -->
   <section class="operation-sec bg-dark position-relative sec-padding">
      <div class="operate-bg jarallax bg-cover position-absolute" data-speed="0.8"
         style="background-image: url('{{ asset('site/images/opt-bg.png') }}');"></div>

      <div class="container ct-container">
         <div class="row">
            <div class="col-lg-7 pe-lg-5">
               <span class="sub-title2 fadeInUp single">How We Work</span>
               <h2 class="sec-title text-info">
                  One group, three ways we help you move, invest and grow.
               </h2>

               <div class="ct-tab nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist"
                  aria-orientation="vertical">

                  <button class="nav-link d-flex align-items-center justify-content-between active" id="air-tab"
                     data-bs-toggle="tab" data-bs-target="#air" type="button" role="tab" aria-controls="air"
                     aria-selected="true">
                     <span class="th-info text-start">
                        <span class="h4 d-block">Import &amp; Export Shipping</span>
                        <span class="p d-block">Door-to-door air, sea and road freight for individuals and
                           businesses.</span>
                     </span>
                     <span class="icon-lg rounded-circle"><i class="fa fa-arrow-right"></i></span>
                  </button>

                  <button class="nav-link d-flex align-items-center justify-content-between" id="byroad-tab"
                     data-bs-toggle="tab" data-bs-target="#byroad" type="button" role="tab" aria-controls="byroad"
                     aria-selected="false">
                     <span class="th-info text-start">
                        <span class="h4 d-block">Ecommerce &amp; Last-Mile</span>
                        <span class="p d-block">Package forwarding and last-mile delivery for online purchases.</span>
                     </span>
                     <span class="icon-lg rounded-circle"><i class="fa fa-arrow-right"></i></span>
                  </button>

                  <button class="nav-link d-flex align-items-center justify-content-between" id="road-tab"
                     data-bs-toggle="tab" data-bs-target="#road" type="button" role="tab" aria-controls="road"
                     aria-selected="false">
                     <span class="th-info text-start">
                        <span class="h4 d-block">Real Estate &amp; Property</span>
                        <span class="p d-block">Property sales, purchases and day-to-day management support.</span>
                     </span>
                     <span class="icon-lg rounded-circle"><i class="fa fa-arrow-right"></i></span>
                  </button>

               </div>
            </div>

            <div class="col-lg-5">
               <div class="tab-content" id="v-pills-tabContent">

                  <div class="tab-pane fade show active" id="air" role="tabpanel" aria-labelledby="air-tab">
                     <div class="operation-content">
                        <div class="operation-thumb">
                           <img src="{{ asset('site/images/tab-main1.jpg') }}" class="img-fluid" alt="Air freight cargo plane">
                        </div>
                        <div class="opt-card bg-white rounded-4 p-4">
                           <span><img src="{{ asset('site/images/tab-icon.png') }}" alt="Icon"></span>
                           <h3 class="mt-3 h5">Request a Quote</h3>
                           <p>Tell us what you need shipped and where it's going.</p>
                           <a class="link-btn" href="{{ route('quote') }}">More Details <i
                                 class="fa fa-arrow-right"></i></a>
                        </div>
                     </div>
                  </div>

                  <div class="tab-pane fade" id="byroad" role="tabpanel" aria-labelledby="byroad-tab">
                     <div class="operation-content">
                        <div class="operation-thumb">
                           <img src="{{ asset('site/images/tab-main2.jpg') }}" class="img-fluid" alt="Warehouse inventory storage">
                        </div>
                        <div class="opt-card bg-white rounded-4 p-4">
                           <span><img src="{{ asset('site/images/tab-icon.png') }}" alt="Icon"></span>
                           <h3 class="mt-3 h5">Ecommerce Logistics</h3>
                           <p>See how we forward packages from major online retailers.</p>
                           <a class="link-btn" href="{{ route('our-services') }}#ecommerce-logistics">More Details <i
                                 class="fa fa-arrow-right"></i></a>
                        </div>
                     </div>
                  </div>

                  <div class="tab-pane fade" id="road" role="tabpanel" aria-labelledby="road-tab">
                     <div class="operation-content">
                        <div class="operation-thumb">
                           <img src="{{ asset('site/images/tab-main3.jpg') }}" class="img-fluid" alt="Road logistics delivery truck">
                        </div>
                        <div class="opt-card bg-white rounded-4 p-4">
                           <span><img src="{{ asset('site/images/tab-icon.png') }}" alt="Icon"></span>
                           <h3 class="mt-3 h5">Real Estate &amp; Property Management</h3>
                           <p>Learn how our team supports owners, buyers and investors.</p>
                           <a class="link-btn" href="{{ route('our-services') }}#real-estate">More Details <i
                                 class="fa fa-arrow-right"></i></a>
                        </div>
                     </div>
                  </div>

               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Operation Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Get Started',
    'heading' => 'Ready to ship, invest, or move smarter?',
])

@endsection
