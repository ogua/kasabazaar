@extends('site.layouts.app')

@section('title', 'News')
@section('meta_description', 'News and updates from KASAROSE LOGISTICS — shipping schedules, service announcements and company news.')

@section('content')

   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">News</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">News</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <!-- Blog Section Start -->
   <section class="blog-standard sec-padding">
      <div class="container">

         @if (!$posts)
            <div class="sec-intro mx-auto text-center">
               <h2 class="sec-title">No news just yet</h2>
               <p class="lead">We haven't published anything here yet. In the meantime, our
                  <a href="{{ route('our-services') }}">services</a> pages cover what we do, and our team is always reachable
                  on the <a href="{{ route('contact-us') }}">contact page</a>.</p>
            </div>
         @else
            <div class="row gy-5">
               @foreach ($posts as $post)
                  <div class="col-lg-4 col-md-6">
                     <article class="blog-entry h-100">
                        <div class="entry-thumb">
                           <a href="{{ route('blog-details', $post['slug']) }}">
                              <img src="{{ $post['image'] }}"
                                 alt="{{ $post['title'] }}" class="img-fluid w-100"
                                 loading="lazy">
                           </a>
                        </div>
                        <div class="entry-meta">
                           @if (!empty($post['category']))
                              <span><i class="fa fa-folder"></i> {{ $post['category'] }}</span>
                           @endif
                           @if ($post['posted_at'])
                              <span><i class="fa fa-calendar"></i> {{ $post['posted_at']->format('M j, Y') }}</span>
                           @endif
                        </div>
                        <h3 class="display-5 mb-3">
                           <a href="{{ route('blog-details', $post['slug']) }}">
                              {{ $post['title'] }}
                           </a>
                        </h3>
                        @if (!empty($post['description']))
                           <p>{{ $post['description'] }}</p>
                        @endif
                        <a class="link-btn" href="{{ route('blog-details', $post['slug']) }}">
                           Read More <i class="fa fa-arrow-right"></i>
                        </a>
                     </article>
                  </div>
               @endforeach
            </div>
         @endif

      </div>
   </section>
   <!-- Blog Section End -->

@include('site.partials.cta', [
    'eyebrow' => 'Get Started',
    'heading' => 'Ready to ship with us?',
])

@endsection
