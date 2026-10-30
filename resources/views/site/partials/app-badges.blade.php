{{--
   App Store and Google Play buttons for the KASAROSE customer app (config('website.mobile_app')).
   @include('site.partials.app-badges', ['small' => true]) for the compact footer size.
--}}
   <div class="app-badges{{ ($small ?? false) ? ' app-badges-sm' : '' }}">
      <a href="{{ config('website.mobile_app.ios_url') }}" class="app-badge" target="_blank" rel="noopener"
         aria-label="Download the KASAROSE app on the App Store">
         <i class="fa-brands fa-apple" aria-hidden="true"></i>
         <span><small>Download on the</small><strong>App Store</strong></span>
      </a>
      <a href="{{ config('website.mobile_app.android_url') }}" class="app-badge" target="_blank" rel="noopener"
         aria-label="Get the KASAROSE app on Google Play">
         <i class="fa-brands fa-google-play" aria-hidden="true"></i>
         <span><small>Get it on</small><strong>Google Play</strong></span>
      </a>
   </div>
