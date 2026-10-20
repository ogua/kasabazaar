{{--
   The SMS programme page registered with the messaging carriers (config('group.sms_terms_url')).
   Ported from the pre-rename kasabazar-website in October 2026; /sms-terms.php redirects here.
   Messages still go out under the carrier-registered "KasaBazaar" sender name, so the page keeps it.
--}}
@extends('site.layouts.app')

@section('title', 'SMS Terms & Notifications')
@section('meta_description', 'How KASAROSE LOGISTICS sends SMS text message updates about your shipment, how to opt in and out, and message frequency and rates.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">SMS Terms &amp; Notifications</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">SMS Terms &amp; Notifications</li>
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
               <p class="text-mute">Last updated: October 20, 2026</p>

               <p>This page explains the SMS (text message) programme that KASAROSE LOGISTICS sends under the
                  <strong>KasaBazaar</strong> name. It covers who can receive these messages, how you opt in, what
                  you'll be sent, and how to opt out.</p>

               <h3 class="display-5 mt-5">Who Sends These Messages</h3>
               <p>Shipment and delivery text messages are sent by KASAROSE LOGISTICS, operated by
                  {{ config('group.company.legal_name') }}, under the <strong>KasaBazaar</strong> name, for shipments
                  booked with us &mdash; whether you requested a quote or contacted us through this website, spoke with
                  our staff by phone, or visited one of our offices in person.</p>

               <h3 class="display-5 mt-5">How You Opt In</h3>
               <p>You opt in by providing your mobile number and agreeing to receive SMS updates:</p>
               <ul class="ct-list">
                  <li>When you submit our <a href="{{ route('quote') }}">quote request form</a> or
                     <a href="{{ route('contact-us') }}">contact form</a> on this website, provide a phone number and
                     tick the SMS consent box.</li>
                  <li>When a shipment is booked with us, in person or by phone, and you give our staff your mobile
                     number for shipment updates.</li>
               </ul>
               <p>Opting in is not a condition of using our services. Providing your number and agreeing to
                  updates is a separate choice from simply requesting a quote or asking a question.</p>

               <h3 class="display-5 mt-5" id="verbal-script">Verbal &amp; In-Person Opt-In Script</h3>
               <p>When a shipment is booked by phone or in person, our staff read the following to the customer
                  before recording their number for SMS updates:</p>
               <blockquote class="border-start border-3 ps-3 py-2 my-3">
                  <p class="mb-0">"Before I finish booking your shipment with <strong>KasaBazaar</strong> &mdash;
                     can I get a mobile number to send you free text updates about this shipment? That's things
                     like pickup, arrival at the port, out-for-delivery and delivery confirmation, and payment
                     receipts. You'll typically get 3 to 10 texts depending on your shipment's activity. Message
                     and data rates may apply. You can reply <strong>STOP</strong> at any time to stop these
                     messages."</p>
               </blockquote>
               <p>This script, and the customer's agreement to it, is what our staff use to record consent in our
                  booking system before any SMS updates are sent. It confirms, in one place:</p>
               <ul class="ct-list">
                  <li>The business sending the messages &mdash; <strong>KasaBazaar</strong></li>
                  <li>That the customer will receive text messages</li>
                  <li>The type of messages &mdash; shipment status and delivery updates, and payment receipts</li>
                  <li>Message frequency &mdash; typically 3&ndash;10 messages per shipment</li>
                  <li>"Msg &amp; data rates may apply"</li>
                  <li>How to stop &mdash; reply STOP at any time</li>
               </ul>

               <h3 class="display-5 mt-5">What You'll Receive</h3>
               <p>If you opt in, we send automated transactional messages triggered by activity on your shipment
                  &mdash; for example:</p>
               <ul class="ct-list">
                  <li>Shipment booking confirmation</li>
                  <li>Pickup and departure updates</li>
                  <li>Arrival at the port and customs clearance status</li>
                  <li>Out-for-delivery and delivery confirmation</li>
                  <li>Payment and balance receipts</li>
                  <li>Tracking-number updates</li>
               </ul>
               <p>These are transactional messages about your own shipment. <strong>We do not send marketing or
                  promotional SMS on this programme.</strong></p>

               <h3 class="display-5 mt-5">Message Frequency &amp; Rates</h3>
               <p>Message frequency varies with shipment activity &mdash; typically 3&ndash;10 messages over the
                  life of a shipment. Message and data rates may apply, depending on your mobile carrier and
                  plan.</p>

               <h3 class="display-5 mt-5">Opting Out &amp; Help</h3>
               <p>Reply <strong>STOP</strong> to any message to unsubscribe at any time, or <strong>HELP</strong>
                  for assistance. You can also contact us using the details below. Mobile carriers are not liable for
                  delayed or undelivered messages.</p>

               <h3 class="display-5 mt-5">Providers &amp; Sharing</h3>
               <p>Messages are delivered through our messaging providers, Twilio and Arkesel, acting as data
                  processors on our behalf. Mobile numbers collected for SMS notifications, and your opt-in
                  consent, are never sold, rented, or shared with third parties, or with other group companies
                  for their own marketing purposes.</p>

               <p>See our <a href="{{ route('privacy-policy') }}#sms-notifications">Privacy Policy</a> and our
                  <a href="{{ route('terms') }}#sms-terms">Terms &amp; Conditions</a> for how we handle your
                  information more generally.</p>

               <h3 class="display-5 mt-5">Contact Us</h3>
               <p>Questions about SMS notifications can be sent to
                  <a href="mailto:{{ config('website.contact.email_support') }}">{{ config('website.contact.email_support') }}</a>
                  or via our <a href="{{ route('contact-us') }}">Contact page</a>.</p>
            </div>
         </div>
      </div>
   </section>

@endsection
