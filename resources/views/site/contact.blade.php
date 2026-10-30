@extends('site.layouts.app')

@section('title', 'Contact Us')
@section('meta_description', 'Contact KASAROSE LOGISTICS — phone, email and our Ghana office in Adako Jachie, Ejisu, Kumasi.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">Contact Us</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <section class="contact-widgets bg-shade sec-padding">
      <div class="container">
         <div class="sec-intro mx-auto text-center">
            <span class="sub-title2 fadeInUp">Contact Us</span>
            <h2 class="sec-title">Start Your Journey with Us</h2>
         </div>
         <div class="row g-4 pt-3">
            <div class="col-lg-4 col-md-6">
               <div class="contact-card d-flex h-100">
                  <span class="icon bg-shade rounded-3">
                     <i class="fa-solid fa-headset fa-2x text-primary" aria-hidden="true"></i>
                  </span>
                  <div class="contact-info">
                     <ul>
                        <li><strong>US Phone:</strong> <a href="tel:{!! config('website.contact.phone_us_1_tel') !!}">{!! config('website.contact.phone_us_1') !!}</a></li>
                        <li><strong>Alt. Phone:</strong> <a href="tel:{!! config('website.contact.phone_us_2_tel') !!}">{!! config('website.contact.phone_us_2') !!}</a></li>
                        <li><strong>Email:</strong> <a href="mailto:{!! config('website.contact.email_support') !!}">{!! config('website.contact.email_support') !!}</a></li>
                     </ul>
                     <a href="mailto:{!! config('website.contact.email_support') !!}" class="ct-url">Send your mail <i class="fa-solid fa-angles-right"></i></a>
                  </div>
                  <h4 class="card-meta">REACH US</h4>
               </div>
            </div>
            <div class="col-lg-4 col-md-6">
               <div class="contact-card d-flex h-100">
                  <span class="icon bg-shade rounded-3">
                     <i class="fa-solid fa-phone fa-2x text-primary" aria-hidden="true"></i>
                  </span>
                  <div class="contact-info">
                     <ul>
                        <li><strong>Ghana Phone:</strong> <a href="tel:{!! config('website.contact.phone_gh_1_tel') !!}">{!! config('website.contact.phone_gh_1') !!}</a></li>
                        <li><strong>Alt. Phone:</strong> <a href="tel:{!! config('website.contact.phone_gh_2_tel') !!}">{!! config('website.contact.phone_gh_2') !!}</a></li>
                     </ul>
                     <a href="tel:{!! config('website.contact.phone_gh_1_tel') !!}" class="ct-url">Call Our Ghana Team <i class="fa-solid fa-angles-right"></i></a>
                  </div>
                  <h4 class="card-meta">GHANA TEAM</h4>
               </div>
            </div>
            <div class="col-lg-4 col-md-6">
               <div class="contact-card d-flex h-100">
                  <span class="icon bg-shade rounded-3">
                     <i class="fa-solid fa-location-dot fa-2x text-primary" aria-hidden="true"></i>
                  </span>
                  <div class="contact-info">
                     <ul>
                        <li><strong>Ghana Office:</strong> {!! config('website.contact.address_gh') !!}</li>
                        <li>Serving the United States &amp; Ghana</li>
                     </ul>
                     <a href="{{ route('our-companies') }}" class="ct-url">Our Group Companies <i class="fa-solid fa-angles-right"></i></a>
                  </div>
                  <h4 class="card-meta">WHERE WE OPERATE</h4>
               </div>
            </div>
         </div>
      </div>
   </section>

   <section class="contact-sec sec-padding">
      <div class="container">
         <div class="sec-intro">
            <span class="sub-title2 fadeInUp single">Get In Touch</span>
            <h2 class="sec-title">Reach Out Anytime for Help</h2>
         </div>
      </div>
      <div class="container">
         <div class="row g-4 mb-5">
            <div class="col-lg-12">
               <iframe src="{!! config('website.contact.map_embed') !!}" title="Our Ghana office in Adako Jachie, Ejisu"
                  style="border:0;width:100%;height:400px;" class="round" allowfullscreen loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
         </div>
      </div>
      <div class="container-fluid">
         <div class="row contact-form-wrap justify-content-center">
            <div class="col-lg-8">
               <div class="contact-form round"
                  style="background: url({{ asset('site/images/form-bg2.jpg') }})no-repeat center center / cover;">
                  <div class="form-inner round">
                     <form id="ajax-contact" method="post" action="{{ route('website.enquiries.store') }}">
                        <input type="hidden" name="form_type" value="contact">
                        @csrf
                        <div class="row">
                           <!-- Full Name -->
                           <div class="form-group col-lg-12">
                              <input type="text" id="name" name="name" class="form-control" placeholder="Your name"
                                 autocomplete="name" required>
                              <i class="fa fa-user"></i>
                           </div>

                           <!-- Email -->
                           <div class="form-group col-lg-6">
                              <input type="email" id="email" name="email" class="form-control" placeholder="Your email"
                                 autocomplete="email" required>
                              <i class="fa fa-envelope"></i>
                           </div>

                           <!-- Phone -->
                           <div class="form-group col-lg-6">
                              <input type="text" id="phone" name="phone" class="form-control" placeholder="Phone no"
                                 autocomplete="tel" required>
                              <i class="fa fa-phone"></i>
                           </div>

                           <!-- Message -->
                           <div class="form-group col-lg-12 top-0">
                              <textarea id="message" name="message" class="form-control" placeholder="Message"
                                 rows="6"></textarea>
                              <i class="fa fa-pen"></i>
                           </div>

                           <div class="col-12 mt-3">
                              @include('site.partials.sms-consent')
                           </div>

                           <div class="col-lg-12">
                              <button type="submit" class="btn btn-primary w-100">Send Message <i
                                    class="fa fa-arrow-right"></i></button>
                           </div>
                           <div class="col-lg-12 mt-3">
                              <div id="form-messages"></div>
                           </div>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>

   <section class="pb-5">
      <div class="container">
         @include('site.partials.app-promo', [
             'heading' => 'Prefer your phone?',
             'text' => 'Get the KASAROSE app to follow your shipments and reach our team from anywhere.',
         ])
      </div>
   </section>

@endsection
