{{--
   The "get the app" card. Place it inside a container.
   @include('site.partials.app-promo', ['heading' => ..., 'text' => ...]) — both are trusted copy
   and may carry HTML entities.
--}}
@php
    $heading ??= 'Your shipments, in your pocket.';
    $text ??= 'Download the KASAROSE app to track your shipments and get status updates wherever you are.';
@endphp
   <aside class="app-promo" aria-label="KASAROSE mobile app">
      <span class="app-promo-icon">
         <img src="{{ asset('site/images/kasarose-icon.png') }}" alt="" width="64" height="64" loading="lazy">
      </span>
      <div class="app-promo-copy">
         <span class="sub-title2">Get the App</span>
         <h2 class="app-promo-title">{!! $heading !!}</h2>
         <p>{!! $text !!}</p>
      </div>
      @include('site.partials.app-badges')
   </aside>
