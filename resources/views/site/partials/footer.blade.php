   <!-- Footer Start -->
   <footer class="footer footer-2 bg-cover jarallax" data-jarallax data-speed=".7">
      <img src="{{ asset('site/images/footer-bg.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row footer-widgets pt-5 gy-5">
            <div class="col-lg-3 col-12">
               <div class="footer-widget about-footer">
                  <div class="f-logo mb-3">
                     <a href="{{ route('home-page') }}" class="d-inline-block">
                        <img src="{{ asset('site/images/kasarose-logistics-logo-h-white.png') }}" alt="{!! config('group.parent.name') !!}" style="max-width:210px;">
                     </a>
                  </div>
                  <p>KASAROSE LOGISTICS: door-to-door air and sea freight, ecommerce package forwarding,
                     real estate and property management &mdash; alongside our sister company KASAROSE MARKET.</p>
                  <h3 class="widget-title mt-4">Get the App</h3>
                  @include('site.partials.app-badges', ['small' => true])
               </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
               <div class="footer-widget">
                  <h3 class="widget-title">Quick Links</h3>
                  <ul>
                     <li><a href="{{ route('about-us') }}">About Us</a></li>
                     <li><a href="{{ route('our-companies') }}">Our Companies</a></li>
                     <li><a href="{{ route('our-services') }}">Our Services</a></li>
                     <li><a href="{{ route('our-projects') }}">Our Work</a></li>
                     <li><a href="{{ route('our-blog') }}">News</a></li>
                     <li><a href="{{ route('faq') }}">FAQ's</a></li>
                     <li><a href="{{ route('our-feeback') }}">Feedback</a></li>
                     <li><a href="{{ route('contact-us') }}">Contact Us</a></li>
                  </ul>
               </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6">
               <div class="footer-widget">
                  <h3 class="widget-title">Our Companies</h3>
                  <ul>
                     @foreach ($groupCompanies as $company)
                     <li><a href="{{ $company['external'] ? $company['url'] : route('our-companies') }}"@if ($company['external']) target="_blank" rel="noopener"@endif>{{ $company['name'] }}</a></li>
                     @endforeach
                  </ul>
               </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6">
               <div class="footer-widget">
                  <h3 class="widget-title">Our Services</h3>
                  <ul>
                     @foreach ($siteServices as $slug => $svc)
                     <li><a href="{{ route('our-services') }}#{!! $slug !!}">{!! $svc['label'] !!}</a></li>
                     @endforeach
                  </ul>
               </div>
            </div>

            <div class="col-lg-3 col-md-12 col-sm-6">
               <div class="footer-widget contact-widget">
                  <h3 class="widget-title">Get In Touch</h3>
                  <div class="opening-inner">
                     <div class="mb-3">
                        <span class="d-block">United States</span>
                        <a href="tel:{!! config('website.contact.phone_us_1_tel') !!}">{!! config('website.contact.phone_us_1') !!}</a><br>
                        <a href="tel:{!! config('website.contact.phone_us_2_tel') !!}">{!! config('website.contact.phone_us_2') !!}</a>
                     </div>
                     <div class="mb-3">
                        <span class="d-block">Ghana</span>
                        <a href="tel:{!! config('website.contact.phone_gh_1_tel') !!}">{!! config('website.contact.phone_gh_1') !!}</a><br>
                        <a href="tel:{!! config('website.contact.phone_gh_2_tel') !!}">{!! config('website.contact.phone_gh_2') !!}</a>
                     </div>
                     <div>
                        <a href="mailto:{!! config('website.contact.email_support') !!}">{!! config('website.contact.email_support') !!}</a>
                     </div>
                  </div>
                  <a href="{{ route('contact-us') }}" class="btn btn-info btn-xs mt-4">Contact Us <span></span></a>
               </div>
            </div>
         </div>
      </div>
      <div class="footer-bottom">
         <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
               <p class="text-info copy-right mb-0">
                  Copyright &copy; {!! date('Y') !!} {!! config('group.parent.name') !!}. All Rights Reserved.</p>
               <nav class="footer-nav">
                  <ul>
                     <li><a href="{{ route('privacy-policy') }}">Privacy Policy</a></li>
                     <li><a href="{{ route('terms') }}">Terms &amp; Conditions</a></li>
                     <li><a href="{{ route('delivery-policy') }}">Delivery Policy</a></li>
                     <li><a href="{{ route('sms-terms') }}">SMS Terms</a></li>
                     <li><a href="{!! url('/admin') !!}" target="_blank" rel="noopener">Staff Login</a></li>
                     <li><a href="{!! config('website.staff_mail_url') !!}" target="_blank" rel="noopener">Staff Mail</a></li>
                  </ul>
               </nav>
            </div>
         </div>
      </div>
   </footer>
   <!-- Footer End -->

   <!-- Scroll Top -->
   <div class="scroll-top">
      <svg class="progress-circle svg-content" height="100%" viewBox="-1 -1 102 102" width="100%">
         <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" fill="none" stroke="black" stroke-width="2"></path>
      </svg>
   </div>
