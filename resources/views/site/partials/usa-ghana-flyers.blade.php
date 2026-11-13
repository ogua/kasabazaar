{{--
   The two management-approved "USA to Ghana" promos, built as live HTML (not images) so phone
   numbers, email and links always follow config/website.php. Photos are stand-ins under
   public/site/images/hero until the flyer photography is supplied.
   @include('site.partials.usa-ghana-flyers') — place inside a container.
--}}
@php
   $c = config('website.contact');
   $qr = asset('site/images/flyers/qr-kasarose.svg');
   $siteHost = preg_replace('#^https?://#', '', config('group.parent.url'));
@endphp
<div class="row g-4 usa-flyers">
   {{-- Flyer A: "Shipping from the USA to Ghana, made simple." --}}
   <div class="col-lg-6">
      <article class="flyer flyer-a h-100" aria-label="Shipping from the USA to Ghana, made simple">
         <header class="flyer-head">
            <img src="{{ asset('site/images/kasarose-logistics-logo-h.png') }}" alt="{{ config('group.parent.name') }}" height="56" loading="lazy">
            <span>Your trusted partner in Ghana shipping</span>
         </header>
         <p class="flyer-eyebrow">Door-to-door</p>
         <h3 class="flyer-title">Shipping from the USA to Ghana, made simple.</h3>
         <p class="flyer-sub">Reliable shipping for homes, families and businesses.</p>
         <div class="flyer-pair">
            <figure style="--photo:url('{{ asset('site/images/hero/pickup-usa.jpg') }}')"><figcaption><small>From U.S. businesses</small>To Ghana businesses</figcaption></figure>
            <figure style="--photo:url('{{ asset('site/images/hero/delivery-ghana-family.jpg') }}')"><figcaption><small>From U.S. homes</small>To Ghana homes</figcaption></figure>
         </div>
         <div class="flyer-contact">
            <h4>Let&rsquo;s get your shipment moving</h4>
            <div class="flyer-contact-grid">
               <div>
                  <p class="flyer-label">USA</p>
                  <a href="tel:{{ $c['phone_us_2_tel'] }}">{{ $c['phone_us_2'] }}</a>
                  <a href="tel:{{ $c['phone_us_1_tel'] }}">{{ $c['phone_us_1'] }}</a>
               </div>
               <div>
                  <p class="flyer-label">Ghana</p>
                  <a href="tel:{{ $c['phone_gh_2_tel'] }}">{{ $c['phone_gh_2'] }}</a>
                  <a href="tel:{{ $c['phone_gh_1_tel'] }}">{{ $c['phone_gh_1'] }}</a>
               </div>
               <img class="flyer-qr" src="{{ $qr }}" alt="QR code to {{ $siteHost }}" width="96" height="96" loading="lazy">
            </div>
            <p class="flyer-line"><span class="flyer-label">Email</span> <a href="mailto:{{ $c['email_support'] }}">{{ $c['email_support'] }}</a></p>
            <p class="flyer-line"><span class="flyer-label">Web</span> <a href="{{ config('group.parent.url') }}">{{ $siteHost }}</a></p>
         </div>
         <footer class="flyer-foot">
            <div><strong>Download the KASAROSE app</strong><span>We deliver more than packages. <em>We deliver trust.</em></span></div>
            <a class="flyer-btn" href="{{ route('quote') }}">Get started</a>
         </footer>
      </article>
   </div>

   {{-- Flyer B: "USA TO GHANA" --}}
   <div class="col-lg-6">
      <article class="flyer flyer-b h-100" aria-label="USA to Ghana door-to-door shipping">
         <header class="flyer-head">
            <img src="{{ asset('site/images/kasarose-logistics-logo-h.png') }}" alt="{{ config('group.parent.name') }}" height="56" loading="lazy">
            <span>Your trusted partner <small>in Ghana shipping</small></span>
         </header>
         <h3 class="flyer-title">USA <em>to</em> Ghana</h3>
         <p class="flyer-sub">Door-to-door shipping for homes and businesses</p>
         <p class="flyer-pill"><i></i> Packages &bull; Business shipments</p>
         <div class="flyer-photo" style="--photo:url('{{ asset('site/images/hero/shipping-usa.jpg') }}')">
            <span>From US businesses &middot; From US homes</span>
         </div>
         <div class="flyer-ready">
            <div><small>Ready to ship?</small>Book your next pickup with Kasarose.</div>
            <a class="flyer-btn" href="{{ route('contact-us') }}">Contact us</a>
         </div>
         <div class="flyer-contact">
            <h4>Contact &amp; follow</h4>
            <div class="flyer-contact-grid">
               <div>
                  <p class="flyer-label">USA</p>
                  <a href="tel:{{ $c['phone_us_2_tel'] }}">{{ $c['phone_us_2'] }}</a>
                  <a href="tel:{{ $c['phone_us_1_tel'] }}">{{ $c['phone_us_1'] }}</a>
               </div>
               <div>
                  <p class="flyer-label">Ghana</p>
                  <a href="tel:{{ $c['phone_gh_2_tel'] }}">{{ $c['phone_gh_2'] }}</a>
                  <a href="tel:{{ $c['phone_gh_1_tel'] }}">{{ $c['phone_gh_1'] }}</a>
               </div>
               <figure class="flyer-qr-wrap"><img class="flyer-qr" src="{{ $qr }}" alt="QR code to {{ $siteHost }}" width="96" height="96" loading="lazy"><figcaption>Scan to download our app</figcaption></figure>
            </div>
            <p class="flyer-line"><span class="flyer-label">Email</span> <a href="mailto:{{ $c['email_support'] }}">{{ $c['email_support'] }}</a></p>
            <p class="flyer-line"><span class="flyer-label">Web</span> <a href="{{ config('group.parent.url') }}">{{ $siteHost }}</a></p>
         </div>
         <footer class="flyer-foot">
            <div><strong>We deliver more than packages.</strong><span><em>We deliver trust.</em></span></div>
            <span class="flyer-route">USA &rarr; Ghana</span>
         </footer>
      </article>
   </div>
</div>
