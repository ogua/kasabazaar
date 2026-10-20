@extends('site.layouts.app')

@section('title', 'Track Your Shipment')
@section('meta_description', 'Track a KASAROSE LOGISTICS shipment — enter your tracking number or shipment reference for a live status update.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Track Your Shipment</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tracking</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <!-- Tracking Section Start -->
   <section class="tracking-page sec-padding">
      <div class="container">
         <div class="row">
            <div class="col-lg-10 mx-auto">

               <div class="tracking-form-wrapper">
                  <div class="sec-intro text-center mb-0">
                     <span class="sub-title2 fadeInUp">Where Is My Shipment?</span>
                     <h2 class="sec-title">Enter Your Tracking Number</h2>
                     <p>Use the tracking number or the shipment reference on your invoice or receipt.</p>
                  </div>
                  <form method="get" action="{{ route('our-tracking') }}" class="tracking-form d-sm-flex">
                     <label for="ref" class="visually-hidden">Tracking number or shipment reference</label>
                     <input type="text" id="ref" name="ref" class="form-control"
                        value="{{ $ref }}"
                        placeholder="Tracking number or reference" required autofocus>
                     <button type="submit" class="btn btn-primary">
                        Track <i class="fa fa-arrow-right"></i><span></span>
                     </button>
                  </form>
               </div>

                              @if ($searched && $shipment === null)
                  <div class="alert alert-danger mt-5" role="alert">
                     <h3 class="display-6 mb-2">No shipment found</h3>
                     <p>We couldn't find a shipment matching
                        <strong>{{ $ref }}</strong>. Please check the number and try
                        again &mdash; it should match the tracking number or shipment reference exactly as it
                        appears on your paperwork.</p>
                     <p class="mb-0">Still stuck? Call us with the number from your paperwork and we'll find it
                        for you, or <a href="{{ route('contact-us') }}">send us a message</a>.</p>
                     @include('site.partials.track-help')
                  </div>

               @elseif ($shipment !== null)
                  <div class="mt-5">
                     <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                           <p class="mb-1">Shipment</p>
                           <h2 class="display-4 mb-0">
                              {{ $shipment->tracking_number ?: $shipment->shipping_reference }}
                           </h2>
                           @if ($shipment->tracking_number && $shipment->shipping_reference)
                              <p class="mb-0">Reference {{ $shipment->shipping_reference }}</p>
                           @endif
                        </div>
                        <span class="badge bg-{!! $status['class'] !!} fs-6 px-4 py-2 mt-3 mt-sm-0">
                           <i class="fa-solid {!! $status['icon'] !!}"></i>
                           {{ $status['label'] }}
                        </span>
                     </div>

                     @if ($status['step'] !== null)
                        @php
                           $step = $status['step'];
                           // The connecting line runs between the first and last badge centres;
                           // fill it (0–1) up to the current stage. The SCSS does the geometry.
                           $ratio = $step > 1 ? round(($step - 1) / (count($stages) - 1), 4) : 0;
                        @endphp
                        <ol class="track-stages" style="--stages: {!! count($stages) !!}; --track-ratio: {!! $ratio !!};"
                           aria-label="Shipment progress">
                           @foreach ($stages as $n => $stage)
                              @php $state = $n < $step ? 'is-done' : ($n === $step ? 'is-current' : 'is-upcoming'); @endphp
                              <li class="track-stage {!! $state !!}"
                                 {!! $n === $step ? 'aria-current="step"' : '' !!}>
                                 <span class="track-stage-img">
                                    <img src="{{ asset('site/'.$stage['image']) }}" alt="" loading="lazy" width="110" height="110">
                                    @if ($n <= $step)
                                       <span class="track-stage-check"><i class="fa-solid fa-check"></i></span>
                                    @endif
                                 </span>
                                 <span class="track-stage-text">
                                    <span class="track-stage-label">{!! $stage['label'] !!}</span>
                                    @if (!empty($stageDates[$n]) && $n <= $step)
                                       <span class="track-stage-date">{{ $stageDates[$n] }}</span>
                                    @endif
                                 </span>
                                 <span class="visually-hidden">
                                    {!! $n < $step ? '(completed)' : ($n === $step ? '(current stage)' : '(upcoming)') !!}
                                 </span>
                              </li>
                           @endforeach
                        </ol>
                     @endif

                     <div class="row g-4 mb-5">
                        @foreach ($facts as $label => $value)
                           <div class="col-md-4 col-sm-6">
                              <div class="bg-shade round p-4 h-100">
                                 <p class="mb-1 small text-uppercase">{!! $label !!}</p>
                                 <p class="mb-0 fw-bold">{{ $value }}</p>
                              </div>
                           </div>
                        @endforeach
                     </div>

                     @if ($items)
                        <h3 class="display-5 mb-4">Items in This Shipment</h3>
                        <div class="row g-4 mb-5">
                           @foreach ($items as $group)
                              <div class="col-md-6">
                                 <div class="track-receiver h-100">
                                    <p class="track-receiver-name">
                                       <i class="fa-solid fa-user"></i>
                                       <span class="small text-uppercase">Receiver</span>
                                       <strong>{{ $group['receiver'] }}</strong>
                                    </p>
                                    <table class="table table-sm mb-0">
                                       <thead>
                                          <tr><th scope="col">Item</th><th scope="col" class="text-center">Qty</th><th scope="col" class="text-end">Box</th></tr>
                                       </thead>
                                       <tbody>
                                          @foreach ($group['items'] as $item)
                                             <tr>
                                                <td>
                                                   <i class="fa-solid {!! $item['vehicle'] ? 'fa-car' : 'fa-box' !!} text-primary me-1"></i>
                                                   {{ $item['name'] }}
                                                </td>
                                                <td class="text-center">{!! $item['quantity'] !!}</td>
                                                <td class="text-end">{{ $item['box'] ?: '—' }}</td>
                                             </tr>
                                          @endforeach
                                       </tbody>
                                    </table>
                                 </div>
                              </div>
                           @endforeach
                        </div>
                     @elseif (! $byTrackingNumber && $shipment->tracking_number)
                        <p class="bg-shade round p-4 mb-5">
                           <i class="fa-solid fa-circle-info text-primary"></i>
                           To see the items in this shipment and who they are going to, search with the tracking
                           number (it starts with <strong>KBZ</strong>) from your receipt or SMS rather than the
                           shipment reference.
                        </p>
                     @endif

                     @if ($timeline)
                        <h3 class="display-5 mb-4">Status History</h3>
                        <ul class="list-unstyled">
                           @foreach ($timeline as $i => $event)
                              <li class="d-flex gap-3 pb-4">
                                 <div class="text-center" style="flex:0 0 24px;">
                                    <span class="d-inline-block rounded-circle {!! $i === 0 ? 'bg-primary' : 'bg-secondary' !!}"
                                       style="width:12px;height:12px;"></span>
                                 </div>
                                 <div>
                                    @php $eventLabel = \App\Services\PublicShipmentTracking::statusMeta($event['status'])['label']; @endphp
                                    <p class="mb-1 fw-bold">{{ $eventLabel }}</p>
                                    @if (!empty($event['location']))
                                       <p class="mb-1"><i class="fa-solid fa-location-dot"></i>
                                          {{ $event['location'] }}</p>
                                    @endif
                                    @if (!empty($event['note']) && strcasecmp(trim($event['note']), $eventLabel) !== 0)
                                       <p class="mb-1">{{ $event['note'] }}</p>
                                    @endif
                                    <p class="mb-0 small text-muted">
                                       {{ $event['happened_at']?->format('M j, Y g:i A') }}
                                    </p>
                                 </div>
                              </li>
                           @endforeach
                        </ul>
                     @else
                        <p>No movement has been logged against this shipment yet. Check back once it has left
                           our facility, or call us for an update.</p>
                     @endif

                     <div class="bg-shade round p-4 mt-4">
                        <p class="mb-0">Something look wrong, or is the status not what you expected? Call us and
                           quote your tracking number &mdash; we'll check it straight away.</p>
                        @include('site.partials.track-help')
                     </div>
                  </div>
               @endif

            </div>
         </div>

         @if ($market)
            <!-- KASAROSE MARKET banner: copy is text, artwork is an inline SVG -->
            <div class="row">
               <div class="col-lg-10 mx-auto">
                  <aside class="market-banner" aria-labelledby="market-banner-title">
                     <div>
                        <span class="market-banner-eyebrow">From our group &middot; {{ $market['name'] }}</span>
                        <h2 class="market-banner-title" id="market-banner-title">Shop Ghana&rsquo;s marketplace, <span>delivered by us.</span></h2>
                        <p class="market-banner-text">Products from trusted Ghanaian vendors &mdash; electronics, fashion,
                           home essentials and more &mdash; brought to your door by the same logistics team moving your
                           shipment.</p>
                        <ul class="market-banner-perks">
                           <li><i class="fa-solid fa-shield-halved"></i>Vetted vendors</li>
                           <li><i class="fa-solid fa-truck-fast"></i>Nationwide delivery</li>
                           <li><i class="fa-solid fa-gavel"></i>Live auctions</li>
                        </ul>
                        <div class="market-banner-cta">
                           <a href="{{ $market['url'] }}" target="_blank" rel="noopener" class="btn btn-primary">
                              Start Shopping <i class="fa fa-arrow-right"></i><span></span>
                           </a>
                           <span class="market-banner-url">{{ preg_replace('#^https?://#', '', $market['url']) }}</span>
                        </div>
                     </div>
                     <svg class="market-banner-art" viewBox="0 0 420 320" role="img" aria-label="A phone showing the marketplace, with shopping bags and a delivery box">
                        <ellipse cx="220" cy="296" rx="180" ry="16" fill="#000" opacity=".18"/>
                        <circle cx="236" cy="150" r="128" fill="#fff" opacity=".06"/>
                        <circle cx="236" cy="150" r="92" fill="#fff" opacity=".06"/>
                        <rect x="150" y="26" width="150" height="268" rx="24" fill="#021528"/>
                        <rect x="160" y="40" width="130" height="240" rx="14" fill="#fff"/>
                        <rect x="205" y="31" width="40" height="5" rx="2.5" fill="#2B4A6F"/>
                        <rect x="160" y="40" width="130" height="38" rx="14" fill="#E4252C"/>
                        <rect x="160" y="62" width="130" height="16" fill="#E4252C"/>
                        <rect x="172" y="52" width="58" height="8" rx="4" fill="#fff"/>
                        <circle cx="274" cy="56" r="7" fill="#fff" opacity=".9"/>
                        <rect x="170" y="88" width="110" height="10" rx="5" fill="#EEF3F8"/>
                        <rect x="170" y="108" width="52" height="58" rx="8" fill="#EEF3F8"/>
                        <rect x="228" y="108" width="52" height="58" rx="8" fill="#EEF3F8"/>
                        <path d="M182 150l10-22 10 14 6-8 8 16z" fill="#2B4A6F"/>
                        <circle cx="254" cy="130" r="13" fill="#E4252C" opacity=".85"/>
                        <rect x="170" y="174" width="52" height="58" rx="8" fill="#EEF3F8"/>
                        <rect x="228" y="174" width="52" height="58" rx="8" fill="#EEF3F8"/>
                        <rect x="181" y="186" width="30" height="30" rx="4" fill="#052240"/>
                        <path d="M240 214h28l-4-24h-20z" fill="#E4252C"/>
                        <rect x="170" y="244" width="110" height="24" rx="12" fill="#052240"/>
                        <rect x="198" y="253" width="54" height="6" rx="3" fill="#fff"/>
                        <path d="M70 168h66l8 110H62z" fill="#E4252C"/>
                        <path d="M86 168v-14a17 17 0 0 1 34 0v14" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
                        <path d="M62 278h82l-2-14H64z" fill="#000" opacity=".15"/>
                        <rect x="88" y="206" width="30" height="6" rx="3" fill="#fff" opacity=".9"/>
                        <path d="M40 222h44l6 72H34z" fill="#fff"/>
                        <path d="M50 222v-10a12 12 0 0 1 24 0v10" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round"/>
                        <circle cx="62" cy="256" r="9" fill="#E4252C"/>
                        <rect x="318" y="190" width="86" height="76" rx="6" fill="#C98B4A"/>
                        <rect x="318" y="190" width="86" height="20" fill="#B47838"/>
                        <rect x="352" y="190" width="18" height="76" fill="#E4252C"/>
                        <rect x="330" y="236" width="18" height="4" rx="2" fill="#fff" opacity=".7"/>
                        <rect x="330" y="244" width="12" height="4" rx="2" fill="#fff" opacity=".7"/>
                        <circle cx="352" cy="132" r="26" fill="#E4252C"/>
                        <path d="M340 132l9 9 15-17" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M30 96l4 10 10 4-10 4-4 10-4-10-10-4 10-4z" fill="#fff" opacity=".7"/>
                        <path d="M390 60l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#fff" opacity=".6"/>
                     </svg>
                  </aside>
               </div>
            </div>
         @endif
      </div>
   </section>
   <!-- Tracking Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Not Shipped Yet?',
    'heading' => 'Get a quote and we&rsquo;ll take it from there.',
])

@endsection
