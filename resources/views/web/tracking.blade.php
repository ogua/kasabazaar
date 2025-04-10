@extends('web.sub-template')

@section('heading','Tracking - KasaBazaar Shipping & Logistics')

@section('sub-heading','Tracking')

@section('main-content')

 <!-- Services Section -->
 <section id="services" class="services section light-background">

  <!-- Section Title -->
  <div class="container section-title" data-aos="fade-up">
      <h2>Track Your Shipment</h2>
  </div><!-- End Section Title -->

  <div class="container">

      <div class="row gy-4">

          <!-- Unbonded Warehouses (Coming Soon) -->
          <div class="col-lg-12 col-md-12" data-aos="fade-up" data-aos-delay="400">
              <div class="service-item position-relative">
                  <div class="icon">
                      <i class="fa-solid fa-thumb-tack"></i>
                  </div>

                  <div>
                      <form action="#" method="post" class="php-email-form">
                          <div class="row gy-3">

                              <div class="col-6">
                                  <label for="">Enter Tracking Number</label>
                                  <input type="text" name="name" class="form-control" required="">
                              </div>

                              <div class="text-right col-12">
                                  <div class="loading">Loading</div>
                                  <div class="error-message"></div>
                                  <div class="sent-message">Your quote request has been successfully sent.
                                      Thank you for choosing KasaBazaar!</div>
                                  <button type="submit" class="btn btn-danger">Track Now</button>
                              </div>

                          </div>
                      </form>
                  </div>


                  <div id="tracking_information"></div>

              </div>
          </div><!-- End Service Item -->

      </div>

  </div>

</section>

<!-- /Services Section -->

<!-- Testimonials Section -->
  @include('web.testimonial')
<!-- /Testimonials Section -->
  
@endsection