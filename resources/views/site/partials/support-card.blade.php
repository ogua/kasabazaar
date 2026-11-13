{{--
   "For your shipment issues — Get live support" card for the tracking page.
   @include('site.partials.support-card', ['reference' => 'KRL1234567']) — quoted in the copy so the
   agent can find the shipment at once.
--}}
@php
   $reference ??= null;
   $primaryTel = config('website.contact.phone_gh_1_tel');
@endphp
<section class="support-card mt-5" aria-labelledby="support-card-title">
   <h2 class="support-card-kicker" id="support-card-title">For your shipment issues</h2>
   <div class="support-card-panel">
      <img class="support-card-photo" src="{{ asset('site/'.config('website.support_image')) }}"
           alt="A KASAROSE LOGISTICS support agent with her phone at the front desk" width="1200" height="675" loading="lazy">
      <div class="support-card-body">
         <h3 class="support-card-title">Get live support</h3>
         <p class="support-card-text">Our team is ready to answer your shipping questions.
            @if ($reference)
               Quote <strong>{{ $reference }}</strong> and we&rsquo;ll check it straight away.
            @endif
         </p>
         <a href="tel:{{ $primaryTel }}" class="support-card-cta">Connect now <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
         @include('site.partials.track-help')
      </div>
   </div>
</section>
