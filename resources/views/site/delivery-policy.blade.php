@extends('site.layouts.app')

@section('title', 'Delivery Policy')
@section('meta_description', 'Delivery Policy for KASAROSE LOGISTICS — estimated delivery time frames for air, ocean, road and ecommerce forwarding shipments, plus how tracking and delivery issues are handled.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Delivery Policy</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Delivery Policy</li>
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
         <div class="row justify-content-center">
            <div class="col-lg-9">
               <p class="text-mute">Last updated: August 20, 2026</p>

               <p>This Delivery Policy explains how shipping requests submitted through this website are handled,
                  and what to expect around transit times, tracking and delivery issues. It applies to import &amp;
                  export shipments arranged through KASAROSE LOGISTICS, including the door-to-door service we
                  previously operated as RDD Shipping.</p>

               <h3 class="display-5 mt-5">How a Shipment Request Works</h3>
               <p>Submitting our <a href="{{ route('quote') }}">quote request form</a> is an inquiry, not a booking. After you
                  submit your shipment details (service type, weight, dimensions, origin and destination), our team
                  reviews them and follows up directly by phone or email to confirm pricing, timing and next steps
                  before anything is booked or charged.</p>

               <h3 class="display-5 mt-5">Delivery Time Frames</h3>
               <p>The estimated delivery time frames below apply to shipments arranged and carried out by
                  KASAROSE LOGISTICS. Each estimate is measured from the date a shipment is collected from you or
                  received at our facility &mdash; not from the date a quote is requested.</p>

               <div class="table-responsive">
                  <table class="table table-bordered align-middle">
                     <thead>
                        <tr>
                           <th scope="col">Service</th>
                           <th scope="col">Estimated Delivery Time</th>
                           <th scope="col">Notes</th>
                        </tr>
                     </thead>
                     <tbody>
@foreach ($deliveryTimeframes as $tf)
                        <tr>
                           <th scope="row">{!! $tf['service'] !!}</th>
                           <td>{!! $tf['estimate'] !!}</td>
                           <td>{!! $tf['notes'] !!}</td>
                        </tr>
@endforeach
                     </tbody>
                  </table>
               </div>

               <p>We respond to every quote request {!! config('website.quote_response_time') !!} to confirm pricing and the
                  delivery window for your specific shipment before anything is booked or charged.</p>

               <p>These ranges are estimates, not guarantees. Actual transit time varies with origin and
                  destination, carrier schedules, customs clearance, public holidays and weather. If a shipment
                  looks likely to fall outside the range above, we will contact you with a revised delivery
                  date.</p>

               <h3 class="display-5 mt-5">Tracking Your Shipment</h3>
               <p>Once a shipment is booked you can follow it on our <a href="{{ route('our-tracking') }}">Tracking</a> page
                  using the tracking number or shipment reference we issue you.</p>

               <h3 class="display-5 mt-5">Delays, Damage &amp; Delivery Issues</h3>
               <p>If a shipment is delayed, damaged, or doesn't arrive as expected, contact us as soon as possible
                  so we can look into it. We can't guarantee outcomes for issues
                  caused by customs holds, carrier delays, incorrect address information, or events outside our
                  control, but we will work with you to resolve the issue.</p>

               <h3 class="display-5 mt-5">Ecommerce Package Forwarding</h3>
               <p>For ecommerce package forwarding (Amazon, eBay, Walmart, Best Buy and similar), delivery of the
                  original retail order is the retailer's responsibility up to the point we receive it; from there,
                  the same forwarding, tracking and delay handling described above applies.</p>

               <h3 class="display-5 mt-5">Orders Placed on KASAROSE MARKET</h3>
               <p><strong>This policy does not cover orders bought on
                  <a href="{{ $marketUrl }}" target="_blank" rel="noopener">KASAROSE MARKET</a></strong>,
                  the group's ecommerce marketplace. Marketplace orders are delivered within Ghana on their own
                  timeframes &mdash; typically 1&ndash;3 business days in Accra and Kumasi, 3&ndash;7 business days
                  in other regions &mdash; and are governed by the KASAROSE MARKET delivery and returns policies.</p>
               <p>We carry a share of those marketplace deliveries on KASAROSE MARKET's behalf, but KASAROSE MARKET remains
                  the point of contact for anything ordered there. Raise marketplace delivery questions with
                  KASAROSE MARKET rather than with us.</p>

               <h3 class="display-5 mt-5">Changes to This Policy</h3>
               <p>We may update this Delivery Policy from time to time. Changes will be posted on this page with an
                  updated "Last updated" date.</p>

               <h3 class="display-5 mt-5">Contact Us</h3>
               <p>Questions about a shipment or this policy can be sent to
                  <a href="mailto:{!! config('website.contact.email_support') !!}">{!! config('website.contact.email_support') !!}</a>
                  or via our <a href="{{ route('contact-us') }}">Contact page</a>. See also our
                  <a href="{{ route('privacy-policy') }}">Privacy Policy</a>,
                  <a href="{{ route('terms') }}">Terms &amp; Conditions</a> and
                  <a href="{{ route('our-companies') }}">Our Companies</a>.</p>
            </div>
         </div>
      </div>
   </section>

@endsection
