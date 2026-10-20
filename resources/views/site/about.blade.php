@extends('site.layouts.app')

@section('title', 'About Us')
@section('meta_description', 'About KASAROSE LOGISTICS — door-to-door freight, real estate, property management, and our sister company KASAROSE MARKET.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
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

   <!-- About Section Start -->
   <section class="about-details sec-padding">
      <div class="container">
         <div class="row">
            <div class="col-lg-6">
               <div class="about-thumb me-lg-4">
                  <img class="img-fluid" src="{{ asset('site/images/about-d.jpg') }}" alt="">
                  <h2 class="display-2">KASAROSE LOGISTICS</h2>
                  <a href="{{ route('quote') }}" class="btn btn-primary">Request a Quote! <i class="fa fa-arrow-right"></i> <span></span>
                  </a>
               </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0">
               <div class="row gy-4 abs-info-wrap">
                  <div class="col-lg-6 col-sm-6">
                     <div class="abs-info">
                        <h3 class="display-5">Group Companies</h3>
                        <div class="d-flex align-items-center justify-content-between border-bottom">
                           <i class="fa-solid fa-sitemap fa-2x text-primary" aria-hidden="true"></i>
                           <span class="purecounter display-1" data-purecounter-end="2">2</span>
                        </div>
                        <p>KASAROSE LOGISTICS and KASAROSE MARKET, working as one group.</p>
                     </div>
                  </div>
                  <div class="col-lg-6 col-sm-6">
                     <div class="abs-info">
                        <h3 class="display-5">Countries Served</h3>
                        <div class="d-flex align-items-center justify-content-between border-bottom">
                           <i class="fa-solid fa-earth-africa fa-2x text-primary" aria-hidden="true"></i>
                           <span class="purecounter display-1" data-purecounter-end="2">2</span>
                        </div>
                        <p>Operating across the United States and Ghana.</p>
                     </div>
                  </div>
                  <div class="col-lg-6 col-sm-6">
                     <div class="abs-info">
                        <h3 class="display-5">Core Services</h3>
                        <div class="d-flex align-items-center justify-content-between border-bottom">
                           <i class="fa-solid fa-layer-group fa-2x text-primary" aria-hidden="true"></i>
                           <span class="purecounter display-1" data-purecounter-end="5">5</span>
                        </div>
                        <p>Shipping, real estate, property management, freight and mobility.</p>
                     </div>
                  </div>
                  <div class="col-lg-6 col-sm-6">
                     <div class="abs-info">
                        <h3 class="display-5">Shared Mission</h3>
                        <div class="d-flex align-items-center justify-content-between border-bottom">
                           <i class="fa-solid fa-compass fa-2x text-primary" aria-hidden="true"></i>
                           <span class="purecounter display-1" data-purecounter-end="1">1</span>
                        </div>
                        <p>One group, working to move goods, people and property forward.</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- About Section End -->

   <!-- Our Story Section Start -->
   <section class="about-sec2 overflow-hidden sec-padding pt-0">
      <div class="container">
         <div class="row align-items-center">
            <div class="col-lg-6">
               <img src="{{ asset('site/images/gallery/shipping-14.jpg') }}" alt="Our warehouse team preparing a shipment"
                  class="img-fluid round">
            </div>
            <div class="col-lg-6 ps-lg-5 mt-5 mt-lg-0">
               <div class="about-content">
                  <span class="sub-title2 fadeInUp single">Our Story</span>
                  <h2 class="sec-title">Twenty years of moving things people care about.</h2>
                  <p>Registered and incorporated in 2020, we became a household name in Ghana and among African
                     communities in the United States &mdash; particularly in Michigan, Indiana, New York and
                     Kentucky &mdash; as a freight forwarding and home delivery service. For most of that time we
                     traded as Rose Door-to-Door Shipping &amp; Delivery; today that work is carried out under the
                     KASAROSE LOGISTICS name.</p>
                  <p>Our staff are experienced in freight forwarding and home delivery, and our shipping experts
                     are there to anticipate and meet your needs whenever they arise. Customers stay with us
                     because their goods arrive as promised.</p>
                  <p>The global supply chain has become complex and vulnerable. We are positioned to navigate it
                     while keeping the part that matters simple: from the sender's door or point of contact to the
                     recipient's door or point of contact, between the USA and Ghana.</p>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Our Story Section End -->

   <!-- Coverage Section Start -->
   <section class="about-sec2 overflow-hidden sec-padding bg-shade">
      <div class="container">
         <div class="row align-items-center flex-lg-row-reverse">
            <div class="col-lg-5">
               <img src="{{ asset('site/images/gallery/shipping-19.jpg') }}" alt="Goods received at our US facility"
                  class="img-fluid round">
            </div>
            <div class="col-lg-7 pe-lg-5 mt-5 mt-lg-0">
               <div class="about-content">
                  <span class="sub-title2 fadeInUp single">Where We Operate</span>
                  <h2 class="sec-title">Operating States &amp; Destinations</h2>
                  <p class="lead">We collect from several states across the United States and ship to Ghana and
                     anywhere in the USA.</p>
                  <div class="row mt-4">
                     <div class="col-sm-7">
                        <h3 class="display-6"><i class="fa-solid fa-map-location-dot text-primary"></i>
                           Operating States (USA)</h3>
                        <ul class="check">
                           <li>Michigan</li>
                           <li>Illinois</li>
                           <li>Indiana</li>
                           <li>New York</li>
                           <li>New Jersey</li>
                           <li>Kentucky</li>
                           <li>Ohio</li>
                        </ul>
                     </div>
                     <div class="col-sm-5">
                        <h3 class="display-6"><i class="fa-solid fa-globe text-primary"></i> Destinations</h3>
                        <ul class="check">
                           <li>Any state in the USA</li>
                           <li>Ghana</li>
                        </ul>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Coverage Section End -->

   <!-- Process Section Start -->
   <section class="process-sec bg-shade sec-padding position-relative overflow-hidden">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="18" fill="none"
                  viewBox="0 0 30 18">
                  <path fill="#e4252c"
                     d="M8.125 15.429h-2.5a.62.62 0 0 1-.442-.189.65.65 0 0 1 0-.909.62.62 0 0 1 .442-.188h2.5c.166 0 .325.068.442.188a.65.65 0 0 1 0 .91.62.62 0 0 1-.442.188m20.313 0h-1.563a.62.62 0 0 1-.442-.189.65.65 0 0 1 0-.909.62.62 0 0 1 .442-.188h1.044l.842-4.618c-.011-2.364-1.973-4.382-4.386-4.382h-4.104l-1.989 9h3.593c.166 0 .325.068.442.188a.653.653 0 0 1 0 .91.62.62 0 0 1-.442.188H17.5a.61.61 0 0 1-.488-.242.65.65 0 0 1-.122-.543L19.162 4.36a.65.65 0 0 1 .22-.361.6.6 0 0 1 .39-.14h4.603C27.476 3.857 30 6.452 30 9.642l-.949 5.261a.65.65 0 0 1-.214.377.6.6 0 0 1-.4.148" />
                  <path fill="#e4252c"
                     d="M24.375 18c-1.722 0-3.125-1.441-3.125-3.214s1.403-3.215 3.125-3.215c1.723 0 3.125 1.442 3.125 3.215S26.098 18 24.375 18m0-5.143c-1.034 0-1.875.866-1.875 1.929s.841 1.928 1.875 1.928 1.875-.865 1.875-1.928-.841-1.929-1.875-1.929M10.625 18C8.903 18 7.5 16.559 7.5 14.786s1.403-3.215 3.125-3.215c1.723 0 3.125 1.442 3.125 3.215S12.348 18 10.625 18m0-5.143c-1.034 0-1.875.866-1.875 1.929s.841 1.928 1.875 1.928 1.875-.865 1.875-1.928c0-1.064-.841-1.929-1.875-1.929m-2.5-9h-5a.62.62 0 0 1-.442-.188.65.65 0 0 1 0-.91.62.62 0 0 1 .442-.188h5c.166 0 .325.068.442.189a.65.65 0 0 1 0 .909.62.62 0 0 1-.442.188m0 3.857h-6.25a.62.62 0 0 1-.442-.188.65.65 0 0 1 0-.91.62.62 0 0 1 .442-.187h6.25c.166 0 .325.067.442.188a.65.65 0 0 1 0 .909.62.62 0 0 1-.442.188m0 3.857h-7.5a.62.62 0 0 1-.442-.188.65.65 0 0 1 0-.909.62.62 0 0 1 .442-.188h7.5c.166 0 .325.067.442.188a.65.65 0 0 1 0 .91.62.62 0 0 1-.442.187" />
                  <path fill="#e4252c"
                     d="M17.5 15.429h-4.375a.62.62 0 0 1-.442-.189.65.65 0 0 1 0-.909.62.62 0 0 1 .442-.188H17l2.84-12.857H5.625a.62.62 0 0 1-.442-.189.65.65 0 0 1 0-.909A.62.62 0 0 1 5.625 0h15a.61.61 0 0 1 .488.241.65.65 0 0 1 .122.543L18.11 14.927a.65.65 0 0 1-.22.36.6.6 0 0 1-.39.142" />
               </svg>HOW WE WORK</span>
            <h2 class="sec-title">Getting Your Shipment Moving Is Simple</h2>
         </div>
         <div class="process-wrapper d-flex justify-content-between flex-wrap mt-5">
            <div class="process-card text-center fadeInUp" data-delay=".2">
               <span class="count mx-auto icon-lg bg-shade rounded-circle">01</span>
               <div class="process-thumb rounded-circle d-flex align-items-center justify-content-center">
                  <img src="{{ asset('site/images/st1.png') }}" alt="select service">
               </div>
               <h3 class="display-4">Select a Service</h3>
               <p>Choose shipping, real estate or property management &mdash; whatever you need.</p>
            </div>

            <div class="process-card text-center fadeInUp" data-delay=".4">
               <span class="count mx-auto icon-lg bg-shade rounded-circle">02</span>
               <div class="process-thumb rounded-circle d-flex align-items-center justify-content-center">
                  <img src="{{ asset('site/images/st2.png') }}" alt="share details">
               </div>
               <h3 class="display-4">Share the Details</h3>
               <p>Request a quote or reach out and tell us what you need.</p>
            </div>

            <div class="process-card text-center fadeInUp" data-delay=".6">
               <span class="count mx-auto icon-lg bg-shade rounded-circle">03</span>
               <div class="process-thumb rounded-circle d-flex align-items-center justify-content-center">
                  <img src="{{ asset('site/images/st3.png') }}" alt="we take it from here">
               </div>
               <h3 class="display-4">We Take It From There</h3>
               <p>Our team &mdash; or one of our group companies &mdash; handles the rest.</p>
            </div>
         </div>
      </div>
   </section>
   <!-- Process Section End -->

   <!-- About Section Start -->
   <section class="about-sec3 sec-padding">
      <div class="container">
         <div class="row">
            <div class="col-xl-5 col-lg-7 mx-lg-auto">
               <div class="about-media3 position-relative">
                  <div class="feat-img position-relative">
                     <img src="{{ asset('site/images/about-ft.jpg') }}" class="img-fluid round" alt="about">
                     <div class="experien-stat">
                        <p class="text-info m-0"> <span class="purecounter" data-purecounter-end="3"
                              data-purecounter-duration="0">3</span> Group Companies</p>
                     </div>
                  </div>
                  <div class="d-flex ab-sm-ft align-items-center gap-4">
                     <img class="round img-fluid" src="{{ asset('site/images/about-ft2.png') }}" alt="about">
                     <img class="img-fluid" src="{{ asset('site/images/about-ft3.png') }}" alt="about">
                  </div>
               </div>
            </div>
            <div class="col-xl-7">
               <div class="about-content3">
                  <span class="sub-title2 fadeInUp single">About Our Company</span>
                  <h2 class="sec-title">A group that grew from one idea into two companies.</h2>
                  <p>KASAROSE LOGISTICS began with door-to-door import &amp; export shipping and has
                     grown into a diversified group spanning freight, real estate, property management and
                     ecommerce (KASAROSE MARKET) across the United States and Ghana.</p>

                  <ul class="nav nav-tabs" id="myTab" role="tablist">
                     <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission"
                           type="button" role="tab" aria-controls="mission" aria-selected="true">Our Mission</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="vission-tab" data-bs-toggle="tab" data-bs-target="#vission"
                           type="button" role="tab" aria-controls="vission" aria-selected="false">Our Vision</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="companies-tab" data-bs-toggle="tab" data-bs-target="#companies"
                           type="button" role="tab" aria-controls="companies" aria-selected="false">Our Companies</button>
                     </li>
                  </ul>
                  <div class="tab-content" id="myTabContent">
                     <div class="tab-pane fade show active" id="mission" role="tabpanel" aria-labelledby="mission-tab">
                        <div class="d-sm-flex gap-4 mb-3">
                           <div class="tab-img">
                              <img src="{{ asset('site/images/ab-t1.png') }}" alt="tab" class="rounded-4">
                           </div>
                           <div class="tab-txt">
                              <p>To make trade, property and mobility simpler and more dependable for the people and
                                 businesses we serve &mdash; from a shipment across the ocean to a property down the
                                 street.</p>
                           </div>
                        </div>
                     </div>
                     <div class="tab-pane fade" id="vission" role="tabpanel" aria-labelledby="vission-tab">
                        <div class="d-sm-flex gap-4 mb-3">
                           <div class="tab-img">
                              <img src="{{ asset('site/images/ab-t1.png') }}" alt="tab" class="rounded-4">
                           </div>
                           <div class="tab-txt">
                              <p>To keep growing KASAROSE LOGISTICS' reach across the United States and Ghana,
                                 giving clients more ways to ship, invest and get around.</p>
                           </div>
                        </div>
                     </div>
                     <div class="tab-pane fade" id="companies" role="tabpanel" aria-labelledby="companies-tab">
                        <div class="d-sm-flex gap-4 mb-3">
                           <div class="tab-img">
                              <img src="{{ asset('site/images/ab-t1.png') }}" alt="tab" class="rounded-4">
                           </div>
                           <div class="tab-txt">
                              <p>KASAROSE LOGISTICS handles freight, import &amp; export shipping, real estate and
                                 property management directly. KASAROSE MARKET is our online marketplace, where vetted
                                 Ghanaian vendors sell direct to shoppers nationwide.</p>
                           </div>
                        </div>
                        <a class="link-btn" href="{{ route('our-companies') }}">See Our Companies <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
                  <div class="d-sm-flex align-items-center about-cta gap-5 mt-5">
                     <a href="{{ route('our-companies') }}" class="btn btn-primary">Our Companies <i
                           class="fa fa-arrow-right"></i><span></span> </a>

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
            </div>
         </div>
      </div>
   </section>
   <!-- About Section End -->

   <!-- Why Choose Us Section Start -->
   <section class="sec-padding bg-shade">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">Why Choose Us</span>
            <h2 class="sec-title">We Understand What Our Customers Need</h2>
         </div>
         <div class="row g-4">
            <div class="col-lg-6 col-md-6">
               <div class="bg-white round p-4 h-100 d-flex gap-3">
                  <span class="icon-lg bg-primary rounded-circle text-white flex-shrink-0">
                     <i class="fa-solid fa-truck-fast"></i>
                  </span>
                  <div>
                     <h3 class="display-6">Speed and Reliability</h3>
                     <p class="mb-0">We prioritise timely delivery and dependable service. Efficient routing, real-time tracking and contingency planning keep delays to a minimum, and our reputation rests on a straightforward pick&ndash;package&ndash;ship&ndash;deliver approach.</p>
                  </div>
               </div>
            </div>
            <div class="col-lg-6 col-md-6">
               <div class="bg-white round p-4 h-100 d-flex gap-3">
                  <span class="icon-lg bg-primary rounded-circle text-white flex-shrink-0">
                     <i class="fa-solid fa-tags"></i>
                  </span>
                  <div>
                     <h3 class="display-6">Cost Efficiency</h3>
                     <p class="mb-0">Competitive pricing is essential, so we offer cost-effective options without compromising on the quality of the service. Our rates are affordable and we are happy to be compared.</p>
                  </div>
               </div>
            </div>
            <div class="col-lg-6 col-md-6">
               <div class="bg-white round p-4 h-100 d-flex gap-3">
                  <span class="icon-lg bg-primary rounded-circle text-white flex-shrink-0">
                     <i class="fa-solid fa-comments"></i>
                  </span>
                  <div>
                     <h3 class="display-6">Transparency and Communication</h3>
                     <p class="mb-0">We keep you informed about shipment status, potential delays and the process as a whole, with accessible tracking tools and regular updates rather than silence between collection and delivery.</p>
                  </div>
               </div>
            </div>
            <div class="col-lg-6 col-md-6">
               <div class="bg-white round p-4 h-100 d-flex gap-3">
                  <span class="icon-lg bg-primary rounded-circle text-white flex-shrink-0">
                     <i class="fa-solid fa-arrows-up-down-left-right"></i>
                  </span>
                  <div>
                     <h3 class="display-6">Flexibility and Scalability</h3>
                     <p class="mb-0">Logistics needs change as a business grows. Our solutions scale with fluctuating demand and varying shipment sizes, from a single barrel to a full container.</p>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Why Choose Us Section End -->

   <!-- Team Section Start -->
   <section class="team-sec sec-padding">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">Our People</span>
            <h2 class="sec-title">The Team Behind Your Shipment</h2>
         </div>
         <div class="row gy-5 justify-content-center">
            <div class="col-lg-3 col-sm-6">
               <div class="team-card mx-auto">
                  <div class="team-thumb position-relative">
                     <img src="{{ asset('site/images/team/jw-adel.jpg') }}" alt="J.W. Adel" class="img-fluid w-100" loading="lazy">
                  </div>
                  <h3 class="display-6 mb-1">J.W. Adel</h3>
                  <p class="mb-0">Director</p>
               </div>
            </div>
            <div class="col-lg-3 col-sm-6">
               <div class="team-card mx-auto">
                  <div class="team-thumb position-relative">
                     <img src="{{ asset('site/images/team/rose-n-adel.jpg') }}" alt="Rose N. Adel" class="img-fluid w-100" loading="lazy">
                  </div>
                  <h3 class="display-6 mb-1">Rose N. Adel</h3>
                  <p class="mb-0">Chief Executive Officer</p>
               </div>
            </div>
            <div class="col-lg-3 col-sm-6">
               <div class="team-card mx-auto">
                  <div class="team-thumb position-relative">
                     <img src="{{ asset('site/images/team/tina-arhin.jpg') }}" alt="Tina Arhin" class="img-fluid w-100" loading="lazy">
                  </div>
                  <h3 class="display-6 mb-1">Tina Arhin</h3>
                  <p class="mb-0">Admin Secretary</p>
               </div>
            </div>
            <div class="col-lg-3 col-sm-6">
               <div class="team-card mx-auto">
                  <div class="team-thumb position-relative">
                     <img src="{{ asset('site/images/team/esther-gyebour-fosu.jpg') }}" alt="Esther Gyebour Fosu" class="img-fluid w-100" loading="lazy">
                  </div>
                  <h3 class="display-6 mb-1">Esther Gyebour Fosu</h3>
                  <p class="mb-0">Property Management Head</p>
               </div>
            </div>
            <div class="col-lg-3 col-sm-6">
               <div class="team-card mx-auto">
                  <div class="team-thumb position-relative">
                     <img src="{{ asset('site/images/team/osei-kwaku.jpg') }}" alt="Osei Kwaku" class="img-fluid w-100" loading="lazy">
                  </div>
                  <h3 class="display-6 mb-1">Osei Kwaku</h3>
                  <p class="mb-0">Architect</p>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Team Section End -->

   <!-- Why Us Section Start -->
   <section class="why-us3 sec-padding jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/whu-us-bg.jpg') }}" class="jarallax-img" alt="">
      <div class="container">
         <div class="row">
            <div class="col-lg-6">
               <div class="why-txt">
                  <div class="sec-intro">
                     <span class="sub-title2 fadeInUp single">Why We're Different</span>
                     <h2 class="sec-title text-white">One Group. Two Ways We Can Help.</h2>
                     <p>Whichever of our companies you work with, you get the same commitment to reliable,
                        honest service.</p>
                  </div>
                  <ul class="check">
                     <li>Door-to-door freight, real estate and property management from KASAROSE LOGISTICS</li>
                     <li>Nationwide online shopping from KASAROSE MARKET</li>
                  </ul>
                  <div class="quick-call d-flex align-items-center">
                     <span class="bg-info icon-lg rounded-circle text-primary"><i
                           class="fa-solid fa-phone-volume"></i></span>
                     <div class="conn-txt ms-3">
                        <p class="m-0">Call Us Any Time:</p>
                        <a class="h6 text-info" href="tel:{!! config('website.contact.phone_us_1_tel') !!}">{!! config('website.contact.phone_us_1') !!}</a>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-6">
               <div class="row gy-4">
                  @foreach ($groupCompanies as $company)
                  <div class="col-12">
                     <div class="bg-white round p-4">
                        <h3 class="display-6">{{ $company['name'] }}</h3>
                        <p class="mb-0">{{ $company['tagline'] }}</p>
                     </div>
                  </div>
                  @endforeach
                  <div class="col-12">
                     <a href="{{ route('our-companies') }}" class="btn btn-primary w-100 justify-content-center">See Our Companies
                        <i class="fa fa-arrow-right"></i><span></span></a>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Why Us Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Work With Us',
    'heading' => 'Whatever you need moved, we can help.',
])

@endsection
