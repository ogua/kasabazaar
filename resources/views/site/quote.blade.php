@extends('site.layouts.app')

@section('title', 'Request a Quote')
@section('meta_description', 'Request a shipping quote from KASAROSE LOGISTICS for air, ocean or road freight and ecommerce package forwarding.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Request a Quote</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Request a Quote</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <div class="quote-features sec-padding">
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="quote-wrap d-md-flex justify-content-between gap-4">
                  <div class="qf-card rounded-3">
                     <span class="icon rounded-3"><i class="fa fa-tags fa-2x"></i></span>
                     <h5>Transparent Pricing</h5>
                  </div>
                  <div class="qf-card rounded-3">
                     <span class="icon rounded-3"><i class="fa fa-location-crosshairs fa-2x"></i></span>
                     <h5>Real-Time Tracking</h5>
                  </div>
                  <div class="qf-card rounded-3">
                     <span class="icon rounded-3"><i class="fa fa-headset fa-2x"></i></span>
                     <h5>Dedicated Support</h5>
                  </div>
               </div>
            </div>
         </div>
         <div class="row quote-form-wrap g-0 round">
            <div class="col-lg-4">
               <div class="quote-thumb">
                  <img src="{{ asset('site/images/quote-thumb.jpg') }}" alt="quote" class="img-fluid rounded-2">
               </div>
            </div>
            <div class="col-lg-8 pt-5 pt-lg-0">
               <form id="ajax-contact" method="post" action="{{ route('website.enquiries.store') }}" class="quote-request-form">
                        <input type="hidden" name="form_type" value="quote">
                        @csrf

                  <!-- Personal Info -->
                  <div class="row personal-info gy-3">
                     <h5>Personal Info</h5>
                     <div class="col-md-4 qr-group">
                        <i class="fa fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Your Name" autocomplete="name" required>
                     </div>
                     <div class="col-md-4 qr-group">
                        <i class="fa fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Your Email" autocomplete="email" required>
                     </div>
                     <div class="col-md-4 qr-group">
                        <i class="fa fa-phone"></i>
                        <input type="text" id="phone" name="phone" placeholder="Your Phone" autocomplete="tel" required>
                     </div>
                  </div>

                  <!-- Shipment Info -->
                  <div class="row mt-4">
                     <h5>Shipment Info</h5>
                     <div class="d-sm-flex flex-wrap gap-3">

                        <!-- Freight Type -->
                        <div class="group-field">
                           <select name="ftype" id="ftype" class="tv-select">
                              <option value="">Service Type</option>
                              <option value="Air Freight">Air Freight</option>
                              <option value="Ocean Freight">Ocean Freight</option>
                              <option value="Road Freight">Road Freight</option>
                              <option value="Ecommerce Logistics">Ecommerce Logistics</option>
                           </select>
                        </div>

                        <!-- City Departure -->
                        <div class="group-field">
                           <input type="text" id="departure" name="departure" placeholder="City Departure">
                        </div>

                        <!-- Delivery City -->
                        <div class="group-field">
                           <input type="text" id="city_deliver" name="city_deliver" placeholder="Delivery City">
                        </div>

                        <!-- Weight / Dimensions -->
                        <div class="group-field">
                           <input type="text" id="wight" name="wight" placeholder="Weight">
                        </div>
                        <div class="group-field">
                           <input type="text" id="dymention" name="dymention" placeholder="Dimensions">
                        </div>

                        <!-- Incoterms -->
                        <div class="group-field">
                           <select name="incoterms" id="incoterms" class="tv-select">
                              <option value="">Incoterms (optional)</option>
                              <option value="EXW">EXW</option>
                              <option value="FOB">FOB</option>
                              <option value="CIF">CIF</option>
                              <option value="DAP">DAP</option>
                           </select>
                        </div>

                        <!-- Date -->
                        <div class="group-field">
                           <input type="text" id="ship_date" name="ship_date" placeholder="Preferred Date"
                              data-date-format="mm/dd/yy">
                        </div>

                        <!-- Time -->
                        <div class="group-field">
                           <input type="text" id="ship_time" name="ship_time" placeholder="Preferred Time">
                        </div>

                     </div>

                     <div class="col-12 mt-3">
                        <textarea name="message" id="message" class="form-control" rows="3"
                           placeholder="Anything else we should know?"></textarea>
                     </div>

                     <div class="col-12 mt-3">
                        @include('site.partials.sms-consent')
                     </div>

                     <!-- Submit Button -->
                     <div class="col-lg-5 mt-4">
                        <button type="submit" class="btn btn-primary w-100">
                           Request a Quote <i class="fa fa-arrow-right"></i>
                        </button>
                     </div>

                     <!-- AJAX Response Message -->
                     <div class="col-12 mt-3">
                        <div id="form-messages"></div>
                     </div>
                  </div>
               </form>
            </div>
         </div>
         <div class="row mt-5">
            <div class="col-lg-12">
               <h3 class="display-5">Estimated Delivery Time Frames</h3>
               <p>Every quote request is answered {!! config('website.quote_response_time') !!}. Once a shipment is booked,
                  these are the delivery times you can expect, measured from the date we collect or receive your
                  cargo:</p>
               <div class="row g-4">
@foreach ($deliveryTimeframes as $tf)
                  <div class="col-md-6 col-lg-3">
                     <div class="qf-card rounded-3 h-100 p-4">
                        <h5 class="mb-2">{!! $tf['service'] !!}</h5>
                        <p class="mb-0"><strong>{!! $tf['estimate'] !!}</strong></p>
                     </div>
                  </div>
@endforeach
               </div>
               <p class="mt-4">Estimates only &mdash; customs clearance, carrier schedules and weather can affect
                  transit. See our <a href="{{ route('delivery-policy') }}">Delivery Policy</a> for full details.</p>
            </div>
         </div>
      </div>
   </div>

@endsection
