@extends('site.layouts.app')

@section('title', 'FAQ')
@section('meta_description', 'Frequently asked questions about KASAROSE LOGISTICS — shipping, tracking, real estate and property management.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">FAQ</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">FAQ</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <!-- FAQ Section Start -->
   <section class="faq-sec overflow-hidden sec-padding position-relative">
      <img src="{{ asset('site/images/red-container.png') }}" alt="" class="anim-img right-top moveXS">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">Frequently Asked Questions</span>
            <h2 class="sec-title">Answers About Our Group &amp; Services</h2>
         </div>
         <div class="row gy-4 justify-content-center">
            <div class="col-lg-9">
               <div class="faq-accordion rounded-4" id="tv-accordion">
                  <div class="accordion-item active">
                     <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button display-5" type="button" data-bs-toggle="collapse"
                           data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                           What is KASAROSE LOGISTICS?
                        </button>
                     </h2>
                     <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                        data-bs-parent="#tv-accordion">
                        <div class="accordion-body">
                           <p>KASAROSE LOGISTICS &mdash; known as KasaBazaar until 2026 &mdash; is the parent
                              group behind our own door-to-door freight, import &amp; export shipping, real estate
                              and property management business, plus KASAROSE MARKET (our ecommerce marketplace). See our
                              <a href="{{ route('our-companies') }}">Our Companies</a> page for details on each.</p>
                        </div>
                     </div>
                  </div>
                  <div class="accordion-item">
                     <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button display-5 collapsed" type="button" data-bs-toggle="collapse"
                           data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                           How can I track my shipment?
                        </button>
                     </h2>
                     <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                        data-bs-parent="#tv-accordion">
                        <div class="accordion-body">
                           <p>Enter your tracking number or shipment reference on our
                              <a href="{{ route('our-tracking') }}">Tracking</a> page for a live status update.</p>
                        </div>
                     </div>
                  </div>
                  <div class="accordion-item">
                     <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button display-5 collapsed" type="button" data-bs-toggle="collapse"
                           data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                           How do I request a shipping quote?
                        </button>
                     </h2>
                     <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                        data-bs-parent="#tv-accordion">
                        <div class="accordion-body">
                           <p>Use our <a href="{{ route('quote') }}">Request a Quote</a> form and tell us about your shipment
                              &mdash; service type, weight, and origin/destination. Our team will follow up by phone
                              or email.</p>
                        </div>
                     </div>
                  </div>
                  <div class="accordion-item">
                     <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button display-5 collapsed" type="button" data-bs-toggle="collapse"
                           data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                           Do you handle real estate and property management?
                        </button>
                     </h2>
                     <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                        data-bs-parent="#tv-accordion">
                        <div class="accordion-body">
                           <p>Yes &mdash; KASAROSE LOGISTICS offers real estate guidance alongside property management
                              support. See our <a href="{{ route('our-services') }}#real-estate">Real Estate</a> and
                              <a href="{{ route('our-services') }}#property-management">Property Management</a> briefings on our
                              Services page, or <a href="{{ route('contact-us') }}">contact us</a> directly.</p>
                        </div>
                     </div>
                  </div>
                  <div class="accordion-item">
                     <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button display-5 collapsed" type="button" data-bs-toggle="collapse"
                           data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                           Where does KASAROSE LOGISTICS operate?
                        </button>
                     </h2>
                     <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                        data-bs-parent="#tv-accordion">
                        <div class="accordion-body">
                           <p>Our companies serve clients across the United States and Ghana, with KASAROSE MARKET
                              delivering to every region of Ghana.</p>
                        </div>
                     </div>
                  </div>
                  <div class="accordion-item">
                     <h2 class="accordion-header" id="headingSix">
                        <button class="accordion-button display-5 collapsed" type="button" data-bs-toggle="collapse"
                           data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                           How do I get in touch?
                        </button>
                     </h2>
                     <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix"
                        data-bs-parent="#tv-accordion">
                        <div class="accordion-body">
                           <p>Call, email, or use our <a href="{{ route('contact-us') }}">contact form</a> &mdash; details for
                              both our US and Ghana teams are on the Contact page.</p>
                        </div>
                     </div>
                  </div>
                  <div class="accordion-item">
                     <h2 class="accordion-header" id="headingSeven">
                        <button class="accordion-button display-5 collapsed" type="button" data-bs-toggle="collapse"
                           data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                           How long does delivery take?
                        </button>
                     </h2>
                     <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven"
                        data-bs-parent="#tv-accordion">
                        <div class="accordion-body">
                           <p>Estimated delivery time frames, measured from the date a shipment is collected or
                              received by us:</p>
                           <ul>
@foreach ($deliveryTimeframes as $tf)
                              <li><strong>{!! $tf['service'] !!}:</strong> {!! $tf['estimate'] !!}</li>
@endforeach
                           </ul>
                           <p>These are estimates and can be affected by customs clearance, carrier schedules and
                              weather. Full details are on our <a href="{{ route('delivery-policy') }}">Delivery Policy</a>
                              page, and we confirm the delivery window for your specific shipment
                              {!! config('website.quote_response_time') !!} of your quote request.</p>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- FAQ Section End -->

@php
// FAQPage JSON-LD. Answers are plain-text transcriptions of the accordion above —
// Google only requires the schema to match the visible content semantically, not
// byte-for-byte, so entities are decoded to plain characters here. The delivery
// time-frame answer is built from $deliveryTimeframes and config('website.quote_response_time') (the same
// data the accordion loops over) rather than retyped, so the two can't drift apart.
$delivery_answer_parts = [];
foreach ($deliveryTimeframes as $tf) {
    $delivery_answer_parts[] = html_entity_decode($tf['service'], ENT_QUOTES) . ': ' . html_entity_decode($tf['estimate'], ENT_QUOTES);
}
$delivery_answer = 'Estimated delivery time frames, measured from the date a shipment is collected or received by us: '
    . implode('; ', $delivery_answer_parts)
    . '. These are estimates and can be affected by customs clearance, carrier schedules and weather. Full details '
    . 'are on our Delivery Policy page, and we confirm the delivery window for your specific shipment '
    . config('website.quote_response_time') . ' of your quote request.';

$faq_items = [
    [
        'What is KASAROSE LOGISTICS?',
        'KASAROSE LOGISTICS — known as KasaBazaar until 2026 — is the parent group behind our own '
            . 'door-to-door freight, import & export shipping, real estate and property management business, '
            . 'plus KASAROSE MARKET (our ecommerce marketplace). See our Our Companies '
            . 'page for details on each.',
    ],
    [
        'How can I track my shipment?',
        'Enter your tracking number or shipment reference on our Tracking page for a live status update.',
    ],
    [
        'How do I request a shipping quote?',
        'Use our Request a Quote form and tell us about your shipment — service type, weight, and '
            . 'origin/destination. Our team will follow up by phone or email.',
    ],
    [
        'Do you handle real estate and property management?',
        'Yes — KASAROSE LOGISTICS offers real estate guidance alongside property management support. Visit our '
            . 'Real Estate and Property Management pages, or contact us directly.',
    ],
    [
        'Where does KASAROSE LOGISTICS operate?',
        'Our companies serve clients across the United States and Ghana, with KASAROSE MARKET delivering to '
            . 'every region of Ghana.',
    ],
    [
        'How do I get in touch?',
        'Call, email, or use our contact form — details for both our US and Ghana teams are on the Contact page.',
    ],
    ['How long does delivery take?', $delivery_answer],
];

$faq_json = json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(static function (array $qa): array {
        return [
            '@type'          => 'Question',
            'name'           => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
        ];
    }, $faq_items),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp
@push('structured-data')
   <script type="application/ld+json">{!! $faq_json !!}</script>
@endpush

@include('site.partials.cta', [
    'eyebrow' => 'Still Have Questions?',
    'heading' => 'We&rsquo;re happy to help.',
    'buttons' => [['label' => 'Contact Us', 'href' => route('contact-us'), 'style' => 'primary']],
])

@endsection
