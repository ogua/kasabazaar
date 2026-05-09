@extends('web.sub-template')

@section('heading','Tracking - Rose Door To Door Shipping & Logistics')

@section('sub-heading','Tracking')

@section('main-content')

 <!-- Tracking Section -->
 <section id="tracking" class="services section light-background">

  <div class="container section-title" data-aos="fade-up">
      <h2>Track Your Shipment</h2>
      <p>Enter your tracking number or shipment reference below to get a real-time status update.</p>
  </div>

  <div class="container" data-aos="fade-up" data-aos-delay="100">
      <div class="row justify-content-center">
          <div class="col-lg-8">
              <div class="service-item position-relative p-4">
                  <div class="icon">
                      <i class="fa-solid fa-location-dot"></i>
                  </div>
                  @livewire('shipment-tracker')
              </div>
          </div>
      </div>
  </div>

</section>
<!-- /Tracking Section -->

<!-- Testimonials Section -->
  @include('web.testimonial')
<!-- /Testimonials Section -->
  
@endsection