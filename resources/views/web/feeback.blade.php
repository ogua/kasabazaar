@extends('web.sub-template')

@section('heading','Customer Feedback - Rose Door To Door Shipping & Logistics')

@section('sub-heading','Customer Feedback')

@section('main-content')
    <!-- Feedback Section -->
    <section id="feedback" class="contact section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

          <div class="row gy-4">

            <div class="col-lg-4">
              <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="200">
                <i class="bi bi-chat-heart"></i>
                <h3>We Value Your Feedback</h3>
                <p class="text-center">Your feedback helps us improve our services and serve you better.</p>
              </div>
            </div><!-- End Info Item -->

            <div class="col-lg-4 col-md-6">
              <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="300">
                <i class="bi bi-telephone"></i>
                <h3>Call Us</h3>
                <p><b>USA:</b> +1 (773) 970-0129</p>
                <p><b>Ghana:</b> +233 50 972 5081</p>
              </div>
            </div><!-- End Info Item -->

            <div class="col-lg-4 col-md-6">
              <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="400">
                <i class="bi bi-envelope"></i>
                <h3>Email Us</h3>
                <p>kasabazaar109@gmail.com</p>
              </div>
            </div><!-- End Info Item -->

          </div>

          <div class="row gy-4 mt-4">
            <div class="col-lg-12">
              <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0"  style="color: #fff;"><i class="bi bi-chat-square-text me-2"></i>Share Your Feedback</h5>
                </div>
                <div class="card-body p-4">
                  @livewire('customer-feedback-form')
                </div>
              </div>
            </div><!-- End Feedback Form -->
          </div>

        </div>

      </section><!-- /Feedback Section -->
@endsection
