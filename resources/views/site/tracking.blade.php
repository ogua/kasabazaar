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
            <!-- KASAROSE MARKET banner: copy is text, product photos are artwork (public/site/images/market) -->
            <div class="row">
               <div class="col-lg-10 mx-auto">
                  <aside class="market-banner" aria-labelledby="market-banner-title">
                     <div class="market-banner-body">
                        <img class="market-banner-logo" src="{{ asset('site/images/market/kasarosemarket-poster-logo.png') }}"
                             alt="{{ $market['name'] }}" width="891" height="187" loading="lazy">
                        <h2 class="market-banner-title" id="market-banner-title">Shop Ghana. <span>We&rsquo;ll deliver it to you.</span></h2>
                        <p class="market-banner-text">Find rice, cooking oil, kitchen fixtures, car parts and more.</p>
                        <a href="{{ $market['url'] }}" target="_blank" rel="noopener" class="market-banner-btn">
                           Start Shopping <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <ul class="market-banner-perks">
                           <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Vetted vendors</li>
                           <li><i class="fa-solid fa-truck-fast" aria-hidden="true"></i>Nationwide delivery</li>
                           <li><i class="fa-solid fa-gavel" aria-hidden="true"></i>Live auctions</li>
                        </ul>
                     </div>
                     <picture class="market-banner-art">
                        <source srcset="{{ asset('site/images/market/market-products.webp') }}" type="image/webp">
                        <img src="{{ asset('site/images/market/market-products.jpg') }}" width="990" height="710" loading="lazy"
                             alt="Rice, vegetable oil, a kitchen sink, headlamps, a drive shaft and suspension struts">
                     </picture>
                     <a href="{{ $market['url'] }}" target="_blank" rel="noopener" class="market-banner-footer">
                        <i class="fa-solid fa-globe" aria-hidden="true"></i>{{ preg_replace('#^https?://#', '', $market['url']) }}
                     </a>
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
