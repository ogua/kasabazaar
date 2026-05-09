@extends('web.default-template')

@section('heading','Index - Rose Door to Door Shipping & Delivery Services')

@section('main-content')

@include('web.mothers-day-overlay')
@include('web.birthday-overlay')

<!-- Hero Section -->
<section id="hero" class="hero section dark-background">

    <div class="info d-flex align-items-center">
      <div class="container">
        <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="100">
          <div class="text-center col-lg-7">
            <h2>Welcome to Rose Door to Door Shipping and Delivery Service</h2>
            <p>Whether you’re shipping locally and internationally, Rose Door to Door Shipping and Delivery Service provides. Your deliveries are in trusted hands.</p>
            <a href="#get-started" class="btn-get-started">Get Started</a>
          </div>
        </div>
      </div>
    </div>

    <div id="hero-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">

      <div class="carousel-item">
        <img src="assets/img/hero-carousel/hero-carousel-1.jpg" alt="">
      </div>

      <div class="carousel-item active">
        <img src="assets/img/hero-carousel/hero-carousel-2.jpg" alt="">
      </div>

      <div class="carousel-item">
        <img src="assets/img/hero-carousel/hero-carousel-3.jpg" alt="">
      </div>

      <!-- <div class="carousel-item">
        <img src="assets/img/hero-carousel/hero-carousel-4.jpg" alt="">
      </div>

      <div class="carousel-item">
        <img src="assets/img/hero-carousel/hero-carousel-5.jpg" alt="">
      </div> -->

      <a class="carousel-control-prev" href="#hero-carousel" role="button" data-bs-slide="prev">
        <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
      </a>

      <a class="carousel-control-next" href="#hero-carousel" role="button" data-bs-slide="next">
        <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
      </a>

    </div>

  </section><!-- /Hero Section -->

  <!-- Get Started Section -->
  <section id="get-started" class="get-started section">

    <div class="container">
  
      <div class="row justify-content-between gy-4">
  
        <div class="col-lg-6 d-flex align-items-center" data-aos="zoom-out" data-aos-delay="100">
          <div class="content">
            <h3>Your Trusted Partner for Global Shipments</h3>
            <p>Take your business or personal shipments to the next level with Rose Door to Door Shipping and Delivery Service. Whether you’re sending parcels to Ghana, the USA, or anywhere else, we make the process smooth, reliable, and efficient. Experience our door-to-door delivery service that ensures your package arrives safely and on time, every time.</p>
            <p>Get started today and enjoy the convenience of real-time tracking, reliable customer support, and the best international shipping rates. Let us handle the logistics while you focus on what matters most.</p>
          </div>
        </div>
  
        <div class="col-lg-5" data-aos="zoom-out" data-aos-delay="200">
          @livewire('quote-request-form')
        </div><!-- End Quote Form -->
  
      </div>
  
    </div>
  
  </section><!-- /Get Started Section -->    

  <!-- Constructions Section -->
  <section id="constructions" class="constructions section">

    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
      <h2>Reliable and Efficient Logistics for Every Shipment</h2>
      <p>From construction materials to delicate shipments, we ensure timely, secure, and safe delivery solutions tailored to your needs. See how Rose Door to Door Shipping and Delivery Service handles it all with expertise.</p>
    </div><!-- End Section Title -->
  
    <div class="container">
  
      <div class="row gy-4">
  
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
          <div class="card-item">
            <div class="row">
              <div class="col-xl-5">
                <div class="card-bg"><img src="assets/shipping-images/construction-1.jpg" alt=""></div>
              </div>
              <div class="col-xl-7 d-flex align-items-center">
                <div class="card-body">
                  <h4 class="card-title">Expert Handling for Construction Projects</h4>
                  <p>Whether you are shipping bulk construction materials or equipment, we provide specialized services to ensure your items arrive in perfect condition, no matter the distance.</p>
                </div>
              </div>
            </div>
          </div>
        </div><!-- End Card Item -->
  
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
          <div class="card-item">
            <div class="row">
              <div class="col-xl-5">
                <div class="card-bg"><img src="assets/shipping-images/shipping (2).JPG" alt=""></div>
              </div>
              <div class="col-xl-7 d-flex align-items-center">
                <div class="card-body">
                  <h4 class="card-title">Safe and Timely Deliveries Guaranteed</h4>
                  <p>We understand the importance of deadlines. Our team is committed to providing quick, efficient delivery, ensuring that your goods arrive where you need them, when you need them.</p>
                </div>
              </div>
            </div>
          </div>
        </div><!-- End Card Item -->
  
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
          <div class="card-item">
            <div class="row">
              <div class="col-xl-5">
                <div class="card-bg"><img src="assets/shipping-images/shipping (12).JPG" alt=""></div>
              </div>
              <div class="col-xl-7 d-flex align-items-center">
                <div class="card-body">
                  <h4 class="card-title">Handling Large-Scale Logistics with Precision</h4>
                  <p>From construction site deliveries to intercontinental shipping, our team ensures every shipment is tracked, handled with care, and delivered efficiently to its destination.</p>
                </div>
              </div>
            </div>
          </div>
        </div><!-- End Card Item -->
  
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="400">
          <div class="card-item">
            <div class="row">
              <div class="col-xl-5">
                <div class="card-bg"><img src="assets/shipping-images/shipping (7).JPG" alt=""></div>
              </div>
              <div class="col-xl-7 d-flex align-items-center">
                <div class="card-body">
                  <h4 class="card-title">Customized Solutions for Every Shipment</h4>
                  <p>Our services cater to various industries. From heavy machinery to delicate materials, we offer tailored solutions that fit your specific shipment needs. Your logistics are in safe hands.</p>
                </div>
              </div>
            </div>
          </div>
        </div><!-- End Card Item -->
  
      </div>
  
    </div>
  
  </section><!-- End Constructions Section -->
  <!-- /Constructions Section -->

  <!-- Services Section -->
    @include('web.our-service')
  <!-- /Services Section -->
  
  <!-- Alt Services Section -->
  @include('web.why-choose-us')
  <!-- /Alt Services Section -->

  <!-- Projects Section -->
  @include('web.projects-display')
  <!-- /Projects Section -->

  <!-- Testimonials Section -->
  <!-- Testimonials Section -->
<!-- /Testimonials Section -->
@include('web.testimonial')
<!-- /Testimonials Section -->

  <!-- Recent Blog Posts Section -->
  <!-- <section id="recent-blog-posts" class="recent-blog-posts section">

    <div class="container section-title" data-aos="fade-up">
      <h2>Recent Blog Posts</h2>
      <p>Stay updated with the latest news, insights, and trends from various industries</p>
    </div>
  
    <div class="container">
  
      <div class="row gy-5">
  
        <div class="col-xl-4 col-md-6">
          <div class="post-item position-relative h-100" data-aos="fade-up" data-aos-delay="100">
  
            <div class="overflow-hidden post-img position-relative">
              <img src="assets/img/blog/blog-1.jpg" class="img-fluid" alt="Technology Blog">
              <span class="post-date">December 12</span>
            </div>
  
            <div class="post-content d-flex flex-column">
  
              <h3 class="post-title">The Rise of Artificial Intelligence: What It Means for the Future</h3>
  
              <div class="meta d-flex align-items-center">
                <div class="d-flex align-items-center">
                  <i class="bi bi-person"></i> <span class="ps-2">Julia Parker</span>
                </div>
                <span class="px-3 text-black-50">/</span>
                <div class="d-flex align-items-center">
                  <i class="bi bi-folder2"></i> <span class="ps-2">Technology</span>
                </div>
              </div>
  
              <hr>
  
              <a href="blog-details.html" class="readmore stretched-link"><span>Read More</span><i class="bi bi-arrow-right"></i></a>
  
            </div>
  
          </div>
        </div>
  
        <div class="col-xl-4 col-md-6">
          <div class="post-item position-relative h-100" data-aos="fade-up" data-aos-delay="200">
  
            <div class="overflow-hidden post-img position-relative">
              <img src="assets/img/blog/blog-2.jpg" class="img-fluid" alt="Sports Blog">
              <span class="post-date">July 17</span>
            </div>
  
            <div class="post-content d-flex flex-column">
  
              <h3 class="post-title">The Future of Sports: How Technology is Shaping Athletic Performance</h3>
  
              <div class="meta d-flex align-items-center">
                <div class="d-flex align-items-center">
                  <i class="bi bi-person"></i> <span class="ps-2">Mario Douglas</span>
                </div>
                <span class="px-3 text-black-50">/</span>
                <div class="d-flex align-items-center">
                  <i class="bi bi-folder2"></i> <span class="ps-2">Sports</span>
                </div>
              </div>
  
              <hr>
  
              <a href="blog-details.html" class="readmore stretched-link"><span>Read More</span><i class="bi bi-arrow-right"></i></a>
  
            </div>
  
          </div>
        </div>
  
        <div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
          <div class="post-item position-relative h-100">
  
            <div class="overflow-hidden post-img position-relative">
              <img src="assets/img/blog/blog-3.jpg" class="img-fluid" alt="Economics Blog">
              <span class="post-date">September 05</span>
            </div>
  
            <div class="post-content d-flex flex-column">
  
              <h3 class="post-title">Global Economic Trends in 2025: What to Expect</h3>
  
              <div class="meta d-flex align-items-center">
                <div class="d-flex align-items-center">
                  <i class="bi bi-person"></i> <span class="ps-2">Lisa Hunter</span>
                </div>
                <span class="px-3 text-black-50">/</span>
                <div class="d-flex align-items-center">
                  <i class="bi bi-folder2"></i> <span class="ps-2">Economics</span>
                </div>
              </div>
  
              <hr>
  
              <a href="blog-details.html" class="readmore stretched-link"><span>Read More</span><i class="bi bi-arrow-right"></i></a>
  
            </div>
  
          </div>
        </div>
  
      </div>
  
    </div>
  
  </section> -->
  <!-- /Recent Blog Posts Section -->

@endsection