@extends('site.layouts.app')

@section('title', 'Our Services')
@section('meta_description', 'KASAROSE LOGISTICS services: air freight, ocean freight, road freight, warehousing, ecommerce logistics, real estate and property management — all on one page.')

@section('content')
@php
// The long-form briefing for each service — image, description, delivery timeframe
// (index into $deliveryTimeframes, where applicable) and which CTA fits. The
// label/tagline/icon/thumb that this merges with come from $siteServices in
// config/website.php, since the footer, canvas-menu and homepage teaser need
// those too; this part is only ever used on this page.
$service_briefs = [
    'air-freight' => [
        'image' => asset('site/images/air.jpg'),
        'alt' => 'Air Freight',
        'timeframe' => 0,
        'body' => 'When speed matters, air freight is the fastest way to move cargo across borders. We handle '
            . 'booking, documentation and coordination so your shipment gets to its destination quickly and '
            . 'safely, whether it\'s a single parcel or a larger commercial shipment.',
        'highlights' => [
            'Fast transit times for time-sensitive cargo',
            'Clear communication from booking to delivery',
            'Support with customs documentation',
            'Real-time shipment tracking',
        ],
        'cta' => ['label' => 'Request an Air Freight Quote', 'href' => route('quote')],
    ],
    'ocean-freight' => [
        'image' => asset('site/images/ship.jpg'),
        'alt' => 'Ocean Freight',
        'timeframe' => 1,
        'body' => 'For bulk, heavy or oversized cargo, ocean freight remains one of the most cost-effective ways '
            . 'to ship internationally. We handle the coordination, documentation and scheduling so your goods '
            . 'move reliably from origin to destination.',
        'highlights' => [
            'Cost-effective for bulk and oversized cargo',
            'Predictable shipping schedules',
            'Support with customs & shipping documentation',
            'Real-time shipment tracking',
        ],
        'cta' => ['label' => 'Request an Ocean Freight Quote', 'href' => route('quote')],
    ],
    'road-freight' => [
        'image' => asset('site/images/gallery/shipping-21.jpg'),
        'alt' => 'One of our delivery vans out on its round',
        'timeframe' => 2,
        'body' => 'Not every shipment needs to cross an ocean. Our road freight service covers local and regional '
            . 'transport, including the last-mile delivery leg that gets a shipment from the port or warehouse to '
            . 'its final destination — a practical complement to our air and ocean freight, and to the package '
            . 'forwarding we handle through ecommerce logistics.',
        'highlights' => [
            'Reliable local & regional delivery',
            'Last-mile support for larger shipments',
            'Flexible scheduling around your timeline',
            'Real-time shipment tracking',
        ],
        'cta' => ['label' => 'Request a Road Freight Quote', 'href' => route('quote')],
    ],
    'warehousing' => [
        'image' => asset('site/images/gallery/shipping-18.jpg'),
        'alt' => 'Our warehouse, where consignments are consolidated before export',
        'timeframe' => null,
        'body' => 'We\'re building out warehousing and storage capacity to give clients more flexibility around '
            . 'when and how their shipments move — useful for consolidating ecommerce orders, holding inventory, '
            . 'or bridging the gap between arrival and final delivery. This service is currently in development; '
            . 'if it\'s something your business needs now, reach out and we\'ll let you know as it becomes available.',
        'highlights' => [],
        'cta' => ['label' => 'Contact Us About Warehousing', 'href' => route('contact-us')],
    ],
    'ecommerce-logistics' => [
        'image' => asset('site/images/gallery/shipping-17.jpg'),
        'alt' => 'Forwarded ecommerce parcels being unloaded for delivery',
        'timeframe' => 3,
        'body' => 'Shop from Amazon, eBay, Walmart, Best Buy and other US retailers, and let us forward your '
            . 'purchases to your doorstep. We consolidate and forward packages as part of the group\'s broader '
            . 'freight network, so you get the same reliability and tracking as our air and ocean freight services.',
        'highlights' => [
            'Forwarding from major US retailers',
            'Package consolidation options',
            'Works alongside our air, ocean & road freight',
            'Real-time shipment tracking',
        ],
        'cta' => ['label' => 'Request a Shipping Quote', 'href' => route('quote')],
    ],
    'real-estate' => [
        'image' => asset('site/images/gallery/construction-1.jpg'),
        'alt' => 'A residential development under construction, plans on site',
        'timeframe' => null,
        'body' => 'Alongside our import &amp; export shipping business, we offer real estate services for clients '
            . 'looking to buy, sell or invest in property. Every situation is different, so we start with a '
            . 'conversation about what you\'re looking for rather than a one-size-fits-all pitch.',
        'highlights' => [
            'Guidance on buying & selling property',
            'Investment property advice',
            'Support alongside our property management team',
        ],
        'cta' => ['label' => 'Talk to Our Team About Real Estate', 'href' => route('contact-us')],
    ],
    'property-management' => [
        'image' => asset('site/images/gallery/shipping-20.jpg'),
        'alt' => 'A residential property and its grounds',
        'timeframe' => null,
        'body' => 'Owning property is easier when someone reliable is looking after it. Our property management '
            . 'service handles the day-to-day so owners can invest with confidence, whether that\'s a single '
            . 'property or a small portfolio — built around what "well managed" looks like for your property.',
        'highlights' => [
            'Day-to-day property oversight',
            'Clear, responsive communication with owners',
            'Support for both single properties & small portfolios',
        ],
        'cta' => ['label' => 'Talk to Our Team About Property Management', 'href' => route('contact-us')],
    ],
];

// Service JSON-LD for each briefing on this page — this page used to be seven
// separate pages, each picking up a Service block automatically from the layout;
// now that it's briefing sections on one page, it builds its own list here the
// same way the FAQ page builds its own FAQPage block from the data it renders from.
$services_graph = array_map(function ($slug) use ($siteServices) {
    return [
        '@type'       => 'Service',
        'name'        => $siteServices[$slug]['label'],
        'description' => $siteServices[$slug]['tagline'],
        'provider'    => ['@type' => 'Organization', 'name' => config('group.parent.name'), 'url' => route('home-page')],
        'areaServed'  => ['United States', 'Ghana'],
        'url'         => route('our-services').'#'.$slug,
    ];
}, array_keys($siteServices));
$services_json = json_encode(['@context' => 'https://schema.org', '@graph' => $services_graph],
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp
@push('structured-data')
   <script type="application/ld+json">{!! $services_json !!}</script>
@endpush

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Our Services</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Services</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <!-- Services Section Start -->
   <div class="single-service bg-shade sec-padding">
      <div class="container">
         <div class="sec-intro mx-auto text-center mb-5">
            <span class="sub-title2 fadeInUp">What We Do</span>
            <h2 class="sec-title">Everything KASAROSE LOGISTICS Offers, on One Page</h2>
            <p class="lead">Freight, ecommerce forwarding, real estate and property management &mdash; open any
               section below for the details, delivery estimates and how to get started.</p>
         </div>

         <!-- Quick jump to each briefing below; replaces the old per-page sidebar. -->
         <nav class="d-flex flex-wrap justify-content-center gap-2 mb-5" aria-label="Jump to a service">
            @foreach ($siteServices as $slug => $svc)
            <a href="#{!! $slug !!}"
               class="d-inline-flex align-items-center gap-2 bg-white round px-4 py-2 text-dark fw-semibold text-decoration-none">
               <i class="fa-solid {!! $svc['icon'] !!} text-primary" aria-hidden="true"></i>{!! $svc['label'] !!}
            </a>
            @endforeach
         </nav>

         <div class="row justify-content-center">
            <div class="col-lg-10">
               <div class="accordion services-accordion" id="services-accordion">
                  @foreach ($siteServices as $slug => $svc)
                  @php $brief = $service_briefs[$slug]; @endphp
                  <div class="accordion-item" id="{!! $slug !!}" style="scroll-margin-top: 140px;">
                     <h2 class="accordion-header">
                        <button class="accordion-button display-5{!! $loop->first ? '' : ' collapsed' !!}" type="button"
                           data-bs-toggle="collapse" data-bs-target="#collapse-{!! $slug !!}"
                           aria-expanded="{!! $loop->first ? 'true' : 'false' !!}" aria-controls="collapse-{!! $slug !!}">
                           <i class="fa-solid {!! $svc['icon'] !!} text-primary me-3" aria-hidden="true"></i>
                           {!! $svc['label'] !!}
                           @if (isset($svc['badge']))
                           <span class="badge bg-primary ms-3">{!! $svc['badge'] !!}</span>
                           @endif
                        </button>
                     </h2>
                     <div id="collapse-{!! $slug !!}"
                        class="accordion-collapse collapse{!! $loop->first ? ' show' : '' !!}"
                        data-bs-parent="#services-accordion">
                        <div class="accordion-body">
                           <div class="row g-4">
                              <div class="col-md-5">
                                 <img src="{{ $brief['image'] }}" alt="{{ $brief['alt'] }}"
                                    class="img-fluid round" loading="lazy">
                              </div>
                              <div class="col-md-7">
                                 <p>{!! $brief['body'] !!}</p>
                                 @if ($brief['timeframe'] !== null)
                                 @php $tf = $deliveryTimeframes[$brief['timeframe']]; @endphp
                                 <p class="mb-2"><strong>Estimated Delivery:</strong>
                                    {!! $tf['estimate'] !!} &mdash; {!! $tf['notes'] !!}
                                    <br><small>Not a guarantee; see our <a href="{{ route('delivery-policy') }}">Delivery Policy</a>
                                       for full details. We confirm your delivery window {!! config('website.quote_response_time') !!}
                                       of your <a href="{{ route('quote') }}">quote request</a>.</small>
                                 </p>
                                 @endif
                                 @if (!empty($brief['highlights']))
                                 <ul class="ct-list mb-3">
                                    @foreach ($brief['highlights'] as $point)
                                    <li>{!! $point !!}</li>
                                    @endforeach
                                 </ul>
                                 @endif
                                 <a href="{{ $brief['cta']['href'] }}" class="btn btn-primary">
                                    {!! $brief['cta']['label'] !!} <i class="fa fa-arrow-right"></i><span></span>
                                 </a>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  @endforeach
               </div>
            </div>
         </div>
      </div>
   </div>
   <!-- Services Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Go Ghana',
    'heading' => 'Ready to ship to Ghana? Let\'s get it moving.',
    'buttons' => [
    ['label' => 'Request a Quote', 'href' => route('quote'), 'style' => 'primary'],
    ['label' => 'Track a Shipment', 'href' => route('our-tracking'), 'style' => 'outline'],
],
])

@endsection
