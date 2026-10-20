   <!-- Canvas Menu Start -->
   <div class="canvas-menu d-flex flex-column">
      <div class="d-flex justify-content-between w-100 mb-4">
         <div class="logo">
            <img src="{{ asset('site/images/kasarose-logistics-logo-h.png') }}" alt="{!! config('group.parent.name') !!}">
         </div>
         <button type="button" class="canvas-close" aria-label="Close">
            <svg width="33" height="34" viewBox="0 0 37 38" fill="none" xmlns="http://www.w3.org/2000/svg">
               <path d="M9.19141 9.80762L27.5762 28.1924" stroke="currentColor" stroke-width="1.5"
                  stroke-linecap="round" stroke-linejoin="round"></path>
               <path d="M9.19141 28.1924L27.5762 9.80761" stroke="currentColor" stroke-width="1.5"
                  stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
         </button>
      </div>
      <p>KASAROSE LOGISTICS spans door-to-door air and sea freight, ecommerce package forwarding, real
         estate and property management &mdash; and, through our sister companies, mobility and online
         retail across the US and Ghana.</p>

      <div class="mt-3">
         <h5>Our Services</h5>
         <nav class="mt-4">
            <ul class="vertical-menu">
               @foreach ($siteServices as $slug => $svc)
               <li><a href="{{ route('our-services') }}#{!! $slug !!}">{!! $svc['label'] !!}</a></li>
               @endforeach
            </ul>
         </nav>
      </div>

      <div class="mt-3">
         <h5>Quick Links</h5>
         <nav class="mt-4">
            <ul class="vertical-menu">
               <li><a href="{{ route('our-tracking') }}">Track a Shipment</a></li>
               <li><a href="{{ route('our-projects') }}">Our Work</a></li>
               <li><a href="{{ route('our-blog') }}">News</a></li>
               <li><a href="{{ route('our-feeback') }}">Give Feedback</a></li>
            </ul>
         </nav>
      </div>

      <div class="mt-4">
         <p class="mb-1"><a href="tel:{!! config('website.contact.phone_us_1_tel') !!}">{!! config('website.contact.phone_us_1') !!}</a> (US)</p>
         <p class="mb-0"><a href="mailto:{!! config('website.contact.email_support') !!}">{!! config('website.contact.email_support') !!}</a></p>
      </div>

      <a href="{{ route('quote') }}" class="btn btn-primary mt-5"> Get a Quote <i class="fa fa-arrow-right"></i><span></span></a>
   </div>
   <!-- Canvas Menu End -->

   <!-- Header Start -->
   <header class="header header-2">
      <div class="sticky-height"></div>
      <div class="header-wrapper">
         <div class="header-top">
            <div class="container ct-container">
               <div class="d-flex justify-content-between align-items-center">
                  <div class="header-infos">
                     <ul class="d-none d-xl-flex flex-wrap align-items-center gap-4">
                        <li>
                           <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 17 17">
                              <path fill="#e4252c"
                                 d="M15.156 10.406q.47.22.688.656.219.438.125.938l-.688 2.969a1.35 1.35 0 0 1-.5.781q-.374.313-.875.313-3.874-.032-7-1.907a13.7 13.7 0 0 1-5-5Q.031 6.032 0 2.156q0-.5.313-.875a1.35 1.35 0 0 1 .78-.5l2.97-.687Q4.563 0 5 .219t.656.687l1.375 3.188q.345.969-.406 1.656l-1.25 1.031a9.54 9.54 0 0 0 3.906 3.907l1.031-1.25q.688-.75 1.657-.407zm-.844 4.344L15 11.781a.45.45 0 0 0-.25-.469l-3.187-1.374q-.281-.094-.47.124l-1.312 1.594q-.249.282-.594.125A10.3 10.3 0 0 1 6.345 9.72 10.3 10.3 0 0 1 4.28 6.875q-.156-.344.125-.594L6 4.97q.22-.188.125-.469L4.75 1.313q-.125-.25-.375-.25h-.094l-2.968.687q-.282.094-.313.406.03 3.594 1.75 6.5a13.46 13.46 0 0 0 4.656 4.656q2.906 1.72 6.5 1.75.313-.03.406-.312" />
                           </svg>
                           <a href="tel:{!! config('website.contact.phone_us_1_tel') !!}">US: {!! config('website.contact.phone_us_1') !!}</a>
                        </li>
                        <li>
                           <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 17 17">
                              <path fill="#e4252c"
                                 d="M15.156 10.406q.47.22.688.656.219.438.125.938l-.688 2.969a1.35 1.35 0 0 1-.5.781q-.374.313-.875.313-3.874-.032-7-1.907a13.7 13.7 0 0 1-5-5Q.031 6.032 0 2.156q0-.5.313-.875a1.35 1.35 0 0 1 .78-.5l2.97-.687Q4.563 0 5 .219t.656.687l1.375 3.188q.345.969-.406 1.656l-1.25 1.031a9.54 9.54 0 0 0 3.906 3.907l1.031-1.25q.688-.75 1.657-.407zm-.844 4.344L15 11.781a.45.45 0 0 0-.25-.469l-3.187-1.374q-.281-.094-.47.124l-1.312 1.594q-.249.282-.594.125A10.3 10.3 0 0 1 6.345 9.72 10.3 10.3 0 0 1 4.28 6.875q-.156-.344.125-.594L6 4.97q.22-.188.125-.469L4.75 1.313q-.125-.25-.375-.25h-.094l-2.968.687q-.282.094-.313.406.03 3.594 1.75 6.5a13.46 13.46 0 0 0 4.656 4.656q2.906 1.72 6.5 1.75.313-.03.406-.312" />
                           </svg>
                           <a href="tel:{!! config('website.contact.phone_gh_1_tel') !!}">Ghana: {!! config('website.contact.phone_gh_1') !!}</a>
                        </li>
                        <li>
                           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 16 12">
                              <path fill="#e4252c"
                                 d="M0 2Q.03 1.157.594.594 1.157.032 2 0h12q.844.03 1.406.594Q15.97 1.157 16 2v8q-.03.844-.594 1.406-.563.563-1.406.594H2q-.843-.03-1.406-.594Q.032 10.843 0 10zm1 0v1.25l6.125 4.469q.875.594 1.75 0L15 3.25V2a.97.97 0 0 0-.281-.719A.97.97 0 0 0 14 1H1.969q-.406 0-.688.281A1.1 1.1 0 0 0 .97 2zm0 2.5V10q0 .438.281.719A.97.97 0 0 0 2 11h12a.97.97 0 0 0 .719-.281A.97.97 0 0 0 15 10V4.5L9.469 8.531q-.656.5-1.469.5a2.36 2.36 0 0 1-1.469-.5z" />
                           </svg>
                           <a href="mailto:{!! config('website.contact.email_support') !!}">{!! config('website.contact.email_support') !!}</a>
                        </li>
                     </ul>
                     <div class="topbar-text dropdown d-xl-none">
                        <a class="topbar-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                           aria-expanded="false">Contact Info</a>
                        <ul class="dropdown-menu">
                           <li>
                              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 17 17">
                                 <path fill="#e4252c"
                                    d="M15.156 10.406q.47.22.688.656.219.438.125.938l-.688 2.969a1.35 1.35 0 0 1-.5.781q-.374.313-.875.313-3.874-.032-7-1.907a13.7 13.7 0 0 1-5-5Q.031 6.032 0 2.156q0-.5.313-.875a1.35 1.35 0 0 1 .78-.5l2.97-.687Q4.563 0 5 .219t.656.687l1.375 3.188q.345.969-.406 1.656l-1.25 1.031a9.54 9.54 0 0 0 3.906 3.907l1.031-1.25q.688-.75 1.657-.407zm-.844 4.344L15 11.781a.45.45 0 0 0-.25-.469l-3.187-1.374q-.281-.094-.47.124l-1.312 1.594q-.249.282-.594.125A10.3 10.3 0 0 1 6.345 9.72 10.3 10.3 0 0 1 4.28 6.875q-.156-.344.125-.594L6 4.97q.22-.188.125-.469L4.75 1.313q-.125-.25-.375-.25h-.094l-2.968.687q-.282.094-.313.406.03 3.594 1.75 6.5a13.46 13.46 0 0 0 4.656 4.656q2.906 1.72 6.5 1.75.313-.03.406-.312" />
                              </svg>
                              <a href="tel:{!! config('website.contact.phone_us_1_tel') !!}">US: {!! config('website.contact.phone_us_1') !!}</a>
                           </li>
                           <li>
                              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 17 17">
                                 <path fill="#e4252c"
                                    d="M15.156 10.406q.47.22.688.656.219.438.125.938l-.688 2.969a1.35 1.35 0 0 1-.5.781q-.374.313-.875.313-3.874-.032-7-1.907a13.7 13.7 0 0 1-5-5Q.031 6.032 0 2.156q0-.5.313-.875a1.35 1.35 0 0 1 .78-.5l2.97-.687Q4.563 0 5 .219t.656.687l1.375 3.188q.345.969-.406 1.656l-1.25 1.031a9.54 9.54 0 0 0 3.906 3.907l1.031-1.25q.688-.75 1.657-.407zm-.844 4.344L15 11.781a.45.45 0 0 0-.25-.469l-3.187-1.374q-.281-.094-.47.124l-1.312 1.594q-.249.282-.594.125A10.3 10.3 0 0 1 6.345 9.72 10.3 10.3 0 0 1 4.28 6.875q-.156-.344.125-.594L6 4.97q.22-.188.125-.469L4.75 1.313q-.125-.25-.375-.25h-.094l-2.968.687q-.282.094-.313.406.03 3.594 1.75 6.5a13.46 13.46 0 0 0 4.656 4.656q2.906 1.72 6.5 1.75.313-.03.406-.312" />
                              </svg>
                              <a href="tel:{!! config('website.contact.phone_gh_1_tel') !!}">Ghana: {!! config('website.contact.phone_gh_1') !!}</a>
                           </li>
                           <li>
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 16 12">
                                 <path fill="#e4252c"
                                    d="M0 2Q.03 1.157.594.594 1.157.032 2 0h12q.844.03 1.406.594Q15.97 1.157 16 2v8q-.03.844-.594 1.406-.563.563-1.406.594H2q-.843-.03-1.406-.594Q.032 10.843 0 10zm1 0v1.25l6.125 4.469q.875.594 1.75 0L15 3.25V2a.97.97 0 0 0-.281-.719A.97.97 0 0 0 14 1H1.969q-.406 0-.688.281A1.1 1.1 0 0 0 .97 2zm0 2.5V10q0 .438.281.719A.97.97 0 0 0 2 11h12a.97.97 0 0 0 .719-.281A.97.97 0 0 0 15 10V4.5L9.469 8.531q-.656.5-1.469.5a2.36 2.36 0 0 1-1.469-.5z" />
                              </svg>
                              <a href="mailto:{!! config('website.contact.email_support') !!}">{!! config('website.contact.email_support') !!}</a>
                           </li>
                        </ul>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="header-nav-wrapper header-sticky">
            <nav class="navbar navbar-expand-xl">
               <div class="container ct-container">
                  <a href="{{ route('home-page') }}" class="navbar-brand">
                     <img src="{{ asset('site/images/kasarose-logistics-logo-h.png') }}" alt="{!! config('group.parent.name') !!}" class="img-fluid">
                  </a>
                  <button class="navbar-toggler offcanvas-nav-btn" type="button">
                     Menu <svg xmlns="http://www.w3.org/2000/svg" width="14" height="12" fill="none"
                        viewBox="0 0 14 12">
                        <path fill="#052240"
                           d="M0 .75Q.063.063.75 0h12.5q.687.063.75.75-.063.687-.75.75H.75Q.063 1.437 0 .75m0 5Q.063 5.063.75 5h12.5q.687.063.75.75-.063.687-.75.75H.75Q.063 6.437 0 5.75m13.25 5.75H.75q-.687-.063-.75-.75.063-.687.75-.75h12.5q.687.063.75.75-.063.687-.75.75" />
                     </svg>
                  </button>
                  <div class="nav-cta d-none d-md-flex order-lg-3">
                     <div class="d-flex align-items-center justify-content-between gap-2">
                        <a href="tel:{!! config('website.contact.phone_us_1_tel') !!}"
                           class="d-none d-xxl-flex align-items-center gap-2 text-white fw-bold text-decoration-none me-2"
                           style="white-space:nowrap;">
                           <i class="fa-solid fa-phone-volume"></i> {!! config('website.contact.phone_us_1') !!}
                        </a>
                        <button class="burger-menu icon-lg bg-light rounded-circle">
                           <svg xmlns="http://www.w3.org/2000/svg" width="14" height="12" fill="none"
                              viewBox="0 0 14 12">
                              <path fill="#052240"
                                 d="M0 .75Q.063.063.75 0h12.5q.687.063.75.75-.063.687-.75.75H.75Q.063 1.437 0 .75m0 5Q.063 5.063.75 5h12.5q.687.063.75.75-.063.687-.75.75H.75Q.063 6.437 0 5.75m13.25 5.75H.75q-.687-.063-.75-.75.063-.687.75-.75h12.5q.687.063.75.75-.063.687-.75.75" />
                           </svg>
                        </button>
                        <a href="{!! url('/admin') !!}" class="btn btn-primary" target="_blank" rel="noopener"> Login <i class="fa fa-arrow-right"></i><span></span>
                        </a>
                     </div>
                  </div>
                  <div class="offcanvas offcanvas-start offcanvas-nav">
                     <div class="offcanvas-header">
                        <a href="{{ route('home-page') }}" class="brand-plate"><img src="{{ asset('site/images/kasarose-logistics-logo-h.png') }}" alt="{!! config('group.parent.name') !!}"></a>
                        <button type="button" class="btn-close bg-primary" data-bs-dismiss="offcanvas"
                           aria-label="Close"></button>
                     </div>

                     <div class="offcanvas-body pt-0 align-items-center justify-content-between">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                           <li class="nav-item">
                              <a class="nav-link{{ request()->routeIs('home-page') ? ' active' : '' }}" href="{{ route('home-page') }}">Home</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link{{ request()->routeIs('about-us') ? ' active' : '' }}" href="{{ route('about-us') }}">About Us</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link{{ request()->routeIs('our-companies') ? ' active' : '' }}" href="{{ route('our-companies') }}">Our Companies</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link{{ request()->routeIs('our-services') ? ' active' : '' }}" href="{{ route('our-services') }}">Services</a>
                           </li>
                           <!-- <li class="nav-item">
                              <a class="nav-link{{ request()->routeIs('quote') ? ' active' : '' }}" href="{{ route('quote') }}">Request Quote</a>
                           </li> -->
                           <li class="nav-item">
                              <a class="nav-link{{ request()->routeIs('our-tracking') ? ' active' : '' }}" href="{{ route('our-tracking') }}">Tracking</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link{{ request()->routeIs('contact-us') ? ' active' : '' }}" href="{{ route('contact-us') }}">Contact Us</a>
                           </li>
                        </ul>
                     </div>
                  </div>
               </div>
            </nav>
         </div>
      </div>
   </header>
   <!-- Header End -->
